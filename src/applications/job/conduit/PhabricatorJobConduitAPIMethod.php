<?php

abstract class PhabricatorJobConduitAPIMethod extends ConduitAPIMethod {

  final public function getApplication() {
    return PhabricatorApplication::getByClass('PhabricatorJobApplication');
  }

  protected function buildProjectInfoDictionary(JobPosting $job) {
    $results = $this->buildProjectInfoDictionaries(array($job));
    return idx($results, $job->getPHID());
  }

  protected function buildProjectInfoDictionaries(array $jobs) {
    assert_instances_of($jobs, 'JobPosting');
    if (!$jobs) {
      return array();
    }

    $result = array();
    foreach ($jobs as $job) {
      $result[$job->getPHID()] = array(
        'id'              => $job->getID(),
        'phid'            => $job->getPHID(),
        'name'            => $job->getName(),
        'description'     => $job->getDescription(),
        'isLead'          => $job->getIsLead(),
        'isCancelled'     => $job->getIsCancelled(),
        'benefit'         => $job->getBenefit(),
        'perk'            => $job->getPerk(),
        'salaryFrom'      => $job->getSalaryFrom(),
        'salaryTo'        => $job->getSalaryTo(),
        'salaryCurrency'  => $job->getSalaryCurrency(),
      );
    }

    return $result;
  }
}
