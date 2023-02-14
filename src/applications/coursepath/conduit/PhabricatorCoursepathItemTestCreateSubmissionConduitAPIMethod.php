<?php

final class PhabricatorCoursepathItemTestCreateSubmissionConduitAPIMethod
  extends PhabricatorCoursepathItemTestConduitAPIMethod {

  public function getAPIMethodName() {
    return 'coursepath.skilltests.submission';
  }

  public function getMethodDescription() {
    return pht('Skill test submission');
  }

  public function getMethodSummary() {
    return pht('Skill test submission.');
  }

  protected function defineParamTypes() {
    return array(
      'testPHID'                => 'required string',
      'answer'                  => 'required string',
      'userPHID'                => 'required string',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $request->getUser();
    $test_phid = $request->getValue('testPHID');
    $user_phid = $request->getValue('userPHID');
    $answer = $request->getValue('answer');

    $score = 0;
    $session = 1;
    $payload = array();
    $xactions = array();

    if ($test_phid) {
      $test = id(new CoursepathItemTestQuery())
        ->setViewer($viewer)
        ->withPHIDs(array($test_phid))
        ->executeOne();

      if (!$test) {
        return array();
      }

      if ($test->getType() != CoursepathItemTest::TYPE_DAILY) {
        $submissions = id(new CoursepathItemTestSubmissionQuery())
            ->setViewer($viewer)
            ->withCreatorPHIDs(array($user_phid))
            ->withTestPHIDs(array($test_phid))
            ->execute();
        $sessions = mpull($submissions, 'getSession');
        if ($sessions) {
          $session = max(array_values($sessions)) + 1;
        }
      }

      if ($test->getIsNotAutomaticallyGraded() == 1
         && $test->getSuiteType() == CoursepathItemTest::SUITE_WPM) {
        $score = (int)$answer;
      }

      if ($answer) {
        $score = $this->setScore($test->getAnswer(), $answer);
      }

      $payload = array(
        'user_phid' => $user_phid,
        'answer' => $answer,
        'score' => $score,
        'session' => $session,
      );

      $xactions[] = id(new CoursepathItemTestTransaction())
        ->setTransactionType(
          CoursepathItemTestSubmissionCreateTransaction::TRANSACTIONTYPE)
        ->setNewValue($payload);

      id(new PhabricatorCoursepathItemTestEditor())
        ->setActor($viewer)
        ->setContentSource($request->newContentSource())
        ->setContinueOnNoEffect(true)
        ->setContinueOnMissingFields(true)
        ->applyTransactions($test, $xactions);
    }

    return array(
      'testPHID' => $test->getPHID(),
      'userPHID' => $user_phid,
      'answer' => $answer,
      'score' => $score,
      'success' => true,
    );
  }

  private function setScore($answer_test, $answer) {
    if ($answer_test == $answer) {
      return 10;
    }

    return 0;
  }

}
