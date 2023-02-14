<?php

final class PhabricatorCoursepathItemTestExerciseConduiAPIMethod
  extends PhabricatorCoursepathItemTestConduitAPIMethod {

  public function getAPIMethodName() {
    return 'coursepath.skilltests.exercise';
  }

  public function getMethodDescription() {
    return pht('Generate Random Exercise Daily');
  }

  public function getMethodSummary() {
    return pht('Generate Random Exercise Daily.');
  }

  protected function defineParamTypes() {
    return array(
      'userPHID'                => 'required string',
      'coursepathItemPHID'      => 'optional string',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $user_phid = $request->getValue('userPHID');
    $coursepath_phid = $request->getValue('coursepathItemPHID');

    $result = array();

    $user = id(new PhabricatorPeopleQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withPHIDs(array($user_phid))
      ->executeOne();

    if (!$user) {
      throw new ConduitException('ERR_USER_NOT_FOUND');
    }

    if (!$coursepath_phid) {
      $enroll = id(new CoursepathItemEnrollmentQuery())
        ->setViewer($request->getUser())
        ->withRegistrarPHIDs(array($user->getPHID()))
        ->executeOne();
      $coursepath_phid = $enroll->getItemPHID();
    }

    $tests = id(new CoursepathItemTestQuery())
      ->setViewer($user)
      ->needOptions(true)
      ->withItemPHIDs(array($coursepath_phid))
      ->withTypes(array(CoursepathItemTest::TYPE_EXERCISE))
      ->execute();

    foreach ($tests as $test) {
      $submission = id(new CoursepathItemTestSubmissionQuery())
        ->setViewer($user)
        ->withTestPHIDs(array($test->getPHID()))
        ->withCreatorPHIDs(array($user->getPHID()))
        ->executeOne();

      if ($submission) {
        continue;
      }

       $result = $this->constructResponse($test);
       break;
    }

    return array(
      'data' => $result,
    );
  }

  private function constructResponse($test) {

    $engine = PhabricatorMarkupEngine::getEngine()
                  ->setConfig('viewer', PhabricatorUser::getOmnipotentUser());
    $parsed_description = $engine->markupText($test->getQuestion());
    if ($parsed_description instanceof PhutilSafeHTML) {
      $parsed_description = $parsed_description->getHTMLContent();
    }


    $responses = array();

    $responses['id']          = $test->getID();
    $responses['type']        = $test->getType();
    $responses['phid']        = $test->getPHID();
    $responses['itemPHID']    = $test->getItemPHID();
    $responses['isSubmitted'] = false;
    $alphabet = range('A', 'Z');
    $index = 0;

    $options = array();
    foreach ($test->getOptions() as $option) {
      $options[$alphabet[$index++]] = $option->getName();
    }

    $responses['fields'] = array(
      'title'             => $test->getTitle(),
      'question'          => $test->getQuestion(),
      'htmlQuestion'      => $parsed_description,
      'answer'            => $test->getAnswer(),
      'type'              => $test->getType(),
      'severity'          => $test->getSeverity(),
      'stack'             => $test->getStack(),
      'suiteType'         => $test->getSuiteType(),
      'isNotAutoGraded'   => (bool)$test->getIsNotAutomaticallyGraded(),
      'testCode'          => $test->getTestCode(),
      'options'           => $options,
    );

    return $responses;
  }
}
