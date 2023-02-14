<?php

final class PhabricatorCoursepathItemTestProgressAPIMethod
  extends PhabricatorCoursepathItemTestConduitAPIMethod {

  public function getAPIMethodName() {
    return 'coursepath.skilltests.progress';
  }

  public function getMethodDescription() {
    return pht('Skilltest progress');
  }

  public function getMethodSummary() {
    return pht('RSP Skilltest Progress.');
  }

  protected function defineParamTypes() {
    return array(
      'userPHID'                => 'required string',
      'coursepathPHID'          => 'required string',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $request->getUser();
    $user_phid = $request->getValue('userPHID');
    $coursepath_phid = $request->getValue('coursepathPHID');
    $submissions = array();

    if ($user_phid) {
      $submissions = id(new CoursepathItemTestSubmissionQuery())
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->needTest(true)
        ->withCreatorPHIDs(array($user_phid))
        ->execute();

      $test_codes = $this->getSkillTestCode($submissions);
      $result = $this->response(
        $viewer,
        $submissions,
        $test_codes,
        $user_phid,
        $coursepath_phid);
    }

    return array(
      'data' => $result,
    );
  }

  private function response($viewer,
    $submissions,
    $codes,
    $user_phid,
    $coursepath_phid) {

    $wpm_result = array();
    $wpm_score = array();
    $basic_temp = array();
    $imd_temp = array();

    $session_temp = array();
    $basic_per_session = array();
    $imd_per_session = array();

    $basic_details = array();
    $basic_detail = array();

    $imd_details = array();
    $imd_detail = array();

    // if coursepath_phid is null
    // trying to get from the first user's coursepath
    if (!$coursepath_phid) {
      $coursepaths = id(new CoursepathItemEnrollmentQuery())
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->withRegistrarPHIDs(array($user_phid))
        ->execute();

      foreach ($coursepaths as $coursepath) {
        $coursepath_phid = $coursepath->getItemPHID();
      }
    }

    foreach ($submissions as $submission) {
      $session_temp[] = (int)$submission->getSession();
    }

    $sessions = array_unique($session_temp);

    foreach ($codes as $code) {
      $skilltest_basic = id(new CoursepathItemTestQuery())
        ->setViewer($viewer)
        ->withTypes(array(CoursepathItemTest::TYPE_QUIZ))
        ->withSeverities(array(CoursepathItemTest::SEVERITY_BASIC))
        ->withItemPHIDs(array($coursepath_phid))
        ->withTestCodes(array($code))
        ->execute();

        if ($skilltest_basic) {
          foreach ($sessions as $session) {
            $skilltest_phids = mpull($skilltest_basic, 'getPHID');
            $submission_basic = id(new CoursepathItemTestSubmissionQuery())
              ->setViewer($viewer)
              ->withTestPHIDs($skilltest_phids)
              ->withSession($session)
              ->withCreatorPHIDs(array($user_phid))
              ->withScore(10)
              ->execute();

            $basic_detail['code'] = $code;
            $basic_detail['session'] = $session;
            $basic_detail['total_test'] = count($skilltest_basic);
            $basic_detail['score'] = count($submission_basic);
            $basic_details[] = $basic_detail;

            $basic_per_session[][$code] = count($submission_basic);
          }

          $basic_temp[] = $this->setScore(
            max($this->arrayColumn($basic_per_session, $code)),
            count($skilltest_basic));
      }

      $skilltest_imd = id(new CoursepathItemTestQuery())
        ->setViewer($viewer)
        ->withTypes(array(CoursepathItemTest::TYPE_QUIZ))
        ->withSeverities(array(CoursepathItemTest::SEVERITY_INTERMEDIATE))
        ->withTestCodes(array($code))
        ->execute();

        if ($skilltest_imd) {
          foreach ($sessions as $session) {
            $skilltest_phids = mpull($skilltest_imd, 'getPHID');
            $submission_imd = id(new CoursepathItemTestSubmissionQuery())
              ->setViewer($viewer)
              ->withTestPHIDs($skilltest_phids)
              ->withCreatorPHIDs(array($user_phid))
              ->withScore(10)
              ->execute();

            $imd_detail['code'] = $code;
            $imd_detail['session'] = $session;
            $imd_detail['total_test'] = count($skilltest_imd);
            $imd_detail['score'] = count($submission_imd);
            $imd_details[] = $imd_detail;

            $imd_per_session[][$code] = count($submission_imd);
          }

        $imd_temp[] = $this->setScore(
          max($this->arrayColumn($imd_per_session, $code)),
          count($skilltest_imd));
      }
    }

    foreach ($submissions as $submission) {
      if ($submission->hasTest()) {
        $test = $submission->getTest();
        if ($test->getIsNotAutomaticallyGraded() == 1) {
            $wpm_score[] = (int)$submission->getAnswer();
        }
      }
    }

    $wpm_result = array(
      'high'
         => $wpm_score ? (int)max($wpm_score) : 0,
      'avg'
        => $wpm_score ? round(array_sum($wpm_score) / count($wpm_score)) : 0,
      'sum'
        => $wpm_score ? array_sum($wpm_score) : 0,
    );

    $final_basic_score = 0;
    $final_intermediate_score = 0;

    if ($basic_temp) {
      $final_basic_score = round($this->setFinalScore(
        array_sum($basic_temp),
        count(array_filter($basic_temp))));
    }

    if ($imd_temp) {
      $final_intermediate_score = round($this->setFinalScore(
        array_sum($imd_temp),
        count(array_filter($imd_temp))));
    }

    $scores = array(
      'wpm' => $wpm_result,
      'basic' => array(),
      'intermediate' => array(),
      'basic_score' => $final_basic_score,
      'intermediate_score' => $final_intermediate_score,
      'details' => array(
        'score_per_session' => $basic_temp,
        'basic_score_detail' => $basic_details,
      ),
    );

    $constraints = array(
      'max' => array(
        'wpm' => CoursepathItemTest::CONSTRAINT_MAX_WPM,
        'stackoverflow' => CoursepathItemTest::CONSTRAINT_MAX_STACKOVERFLOW,
        'basic' => CoursepathItemTest::CONSTRAINT_MAX_BASIC,
        'intermediate' => CoursepathItemTest::CONSTRAINT_MAX_INTERMEDIATE,
      ),

      'min' => array(
        'wpm' => CoursepathItemTest::CONSTRAINT_MIN_WPM,
        'stackoverflow' => CoursepathItemTest::CONSTRAINT_MIN_STACKOVERFLOW,
        'basic' => CoursepathItemTest::CONSTRAINT_MIN_BASIC,
        'intermediate' => CoursepathItemTest::CONSTRAINT_MIN_INTERMEDIATE,
      ),
    );

    $results = array(
      'score' => $scores,
      'constraint' => $constraints,
    );

    return $results;
  }

  private function getSkillTestCode($submissions) {
    $codes = array();
    foreach ($submissions as $submission) {
      if ($submission->hasTest()) {
        $test = $submission->getTest();
        $code = $test->getTestCode();
        if (in_array($code, $codes)) {
          continue;
        }
        $codes[] = $code;
      }
    }

    return $codes;
  }

  private function setScore(int $submission_count, int $test_count) {
    if ($submission_count == 0) {
      return 0;
    }
    return ($submission_count / $test_count) * 100;
  }

  private function setFinalScore(int $max_score, $session) {
    if ($max_score == 0) {
      return 0;
    }
    return $max_score / $session;
  }

  /**
   * array_column function
   * because this current PHP doesn't support it yet
   *
   *
   * This codebase targets PHP 5.2.3, but `array_column()` was not introduced
   * until PHP 5.5.0.
   */
  public function arrayColumn(array $array, $column_key, $index_key = null) {
      $result = array();
      foreach ($array as $sub_array) {
          if (!is_array($sub_array)) {
              continue;
          } else if (is_null($index_key) && array_key_exists(
            $column_key, $sub_array)) {
              $result[] = $sub_array[$column_key];
          } else if (array_key_exists($index_key, $sub_array)) {
              if (is_null($column_key)) {
                  $result[$sub_array[$index_key]] = $sub_array;
              } else if (array_key_exists($column_key, $sub_array)) {
                  $result[$sub_array[$index_key]] = $sub_array[$column_key];
              }
          }
      }
      return $result;
  }
}
