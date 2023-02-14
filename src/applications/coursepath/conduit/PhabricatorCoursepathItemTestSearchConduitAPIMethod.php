<?php

final class PhabricatorCoursepathItemTestSearchConduitAPIMethod
  extends PhabricatorCoursepathItemTestConduitAPIMethod {

  public function getAPIMethodName() {
    return 'coursepath.skilltests.search';
  }

  public function getMethodDescription() {
    return pht('Skill test query search');
  }

  public function getMethodSummary() {
    return pht('Skill test query search.');
  }

  protected function defineParamTypes() {
    return array(
      'itemPHID'        => 'string',
      'testCode'        => 'string',
      'type'            => 'string',
      'severity'        => 'string',
      'stack'           => 'string',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $request->getUser();
    $item_phid = $request->getValue('itemPHID');
    $test_code = $request->getValue('testCode');
    $type = $request->getValue('type');
    $severity = $request->getValue('severity');
    $stack = $request->getValue('stack');

    $tests = array();
    $result = array();

    $tests = id(new CoursepathItemTestQuery())
      ->setViewer($viewer)
      ->needSubmissions(true);

    if ($item_phid) {
      $tests = $tests->withItemPHIDs(array($item_phid));
    }

    if ($type) {
      $tests = $tests->withTypes(array($type));
    }

    if ($severity) {
      $tests = $tests->withSeverities(array($severity));
    }

    if ($test_code) {
      $tests = $tests->withTestCodes(array($test_code));
    }

    if ($stack) {
      $tests = $tests->withStacks(array($stack));
    }

    $tests = $tests->needOptions(true)->execute();
    $result = $this->constructResponse($tests, $viewer);
    return array(
      'data' => $result,
    );
  }

  private function constructResponse($tests, $viewer) {
    $responses = array();
    $response = array();
    $now = phabricator_datetime(PhabricatorTime::getNow(), $viewer);
    $today = date('d', strtotime($now));

    $submit_creator_phid = null;
    $index = 0;
    $alphabet = range('A', 'Z');
    $is_submitted = false;
    foreach ($tests as $test) {
      $options = array();
      foreach ($test->getOptions() as $opt) {
        $options[$alphabet[$index++]] = $opt->getName();
      }

      $submissions = $test->getSubmissions();
      if ($submissions) {
        $last_submitted = end($submissions);
        $date_created = phabricator_datetime(
            $last_submitted->getDateCreated(),
            $viewer);
        $submit_day = date('d', strtotime($date_created));

        $submit_creator_phid = $last_submitted->getCreatorPHID();
        if ($viewer->getPHID() == $submit_creator_phid) {
          if ((int)$today == (int)$submit_day) {
            $is_submitted = true;
          }
        }
      }

      $engine = PhabricatorMarkupEngine::getEngine()
                    ->setConfig('viewer', PhabricatorUser::getOmnipotentUser());
      $parsed_description = $engine->markupText($test->getQuestion());
      if ($parsed_description instanceof PhutilSafeHTML) {
        $parsed_description = $parsed_description->getHTMLContent();
      }

      $response['id'] = $test->getID();
      $response['type'] = 'Skill Test';
      $response['phid'] = $test->getPHID();
      $response['itemPHID'] = $test->getItemPHID();
      $response['isSubmitted'] = $is_submitted;
      $response['fields'] = array(
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
        'options' => $options,
      );

      $is_submitted = false;
      $index = 0;
      $responses[] = $response;
    }

    return $responses;
  }

}
