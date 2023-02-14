<?php

final class PhabricatorJobPostingTechStackEditor
  extends PhabricatorEditor {

  private $request;
  private $job;
  private $continueOnMissingFields = false;

  public function setRequest(AphrontRequest $request) {
    $this->request = $request;
    return $this;
  }

  public function getRequest() {
    return $this->request;
  }

  public function setJobPosting(JobPosting $job) {
    $this->job = $job;
    return $this;
  }

  public function getJobPosting() {
    return $this->job;
  }

  public function setContinueOnMissingFields($continue) {
    $this->continueOnMissingFields = $continue;
    return $this;
  }

  public function apply() {
    $request = $this->requireRequest();
    $actor = $this->requireActor();
    $job = $this->requireJobPosting();
    $coursepath_item = $this->requireCoursepathItem();

    $tech_stack = $this->loadCurrentTechStack($job);
    $tech_stack->setAuthorPHID($actor->getPHID());
    $tech_stack->setCoursepathItemPHID($coursepath_item->getPHID());
    $tech_stack->save();

  }

  private function requireRequest() {
    if (!$this->request) {
      throw new Exception(pht('No request attached!'));
    }

    return $this->request;
  }

  private function requireJobPosting() {
    if (!$this->job) {
      throw new Exception(pht('No job posting selected!'));
    }

    return $this->job;
  }

  private function requireCoursepathItem() {
    if (!$this->request->getArr('coursepathItemPHID')) {
      throw new Exception(pht('No course path item selected!'));
    }

    return id(new CoursepathItemQuery())
            ->withPHIDs($this->request->getArr('coursepathItemPHID'))
            ->setViewer($this->requireActor())
            ->executeOne();
  }

  private function loadCurrentTechStack(JobPosting $job) {
    $app = id(new PhabricatorApplicationQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withClasses(array('PhabricatorProjectApplication'))
      ->executeOne();

    $view_policy = $app->getPolicy(
      PhabricatorPolicyCapability::CAN_VIEW);
    $edit_policy = $app->getPolicy(
      PhabricatorPolicyCapability::CAN_EDIT);

    $current_tech_stack = id(new JobPostingTechStack())->loadOneWhere(
      'postingPHID = %s',
      $job->getPHID());

    if ($current_tech_stack) {
      $current_tech_stack->setViewPolicy($view_policy);
      $current_tech_stack->setEditPolicy($edit_policy);
    } else {
      $current_tech_stack = JobPostingTechStack::initializeNewRspSpec(
                      $this->requireActor(), $job);
    }

    return $current_tech_stack;
  }
}
