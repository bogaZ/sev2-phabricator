<?php

abstract class PhabricatorCoursepathItemTestConduitAPIMethod
  extends ConduitAPIMethod {

  final public function getApplication() {
    return PhabricatorApplication::getByClass(
      'PhabricatorCoursepathApplication');
  }

  protected function buildProjectInfoDictionary(CoursepathItemTest $test) {
    $results = $this->buildProjectInfoDictionaries(array($test));
    return idx($results, $test->getPHID());
  }

  protected function buildProjectInfoDictionaries(array $tests) {
    assert_instances_of($tests, 'CoursepathItemTest');
    if (!$tests) {
      return array();
    }

    $result = array();
    foreach ($tests as $test) {
      $result[$test->getPHID()] = array(
        'id'                => $test->getID(),
        'phid'              => $test->getPHID(),
        'title'             => $test->getName(),
        'question'          => $test->getDescription(),
        'answer'            => $test->getIsLead(),
        'severity'          => $test->getIsCancelled(),
        'status'            => $test->getBenefit(),
        'isNotAutoGraded'   => $test->getIsNotAutomaticallyGraded(),
        'suiteType'         => $test->getSuiteType(),
        'testCode'          => $test->getTestCode(),
      );
    }

    return $result;
  }
}
