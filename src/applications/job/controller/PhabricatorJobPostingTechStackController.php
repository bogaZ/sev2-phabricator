<?php

final class PhabricatorJobPostingTechStackController
  extends PhabricatorJobController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $id = $request->getURIData('id');

    $job = id(new JobPostingQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->needTechStack(true)
      ->executeOne();

    if (!$job) {
      return new Aphront404Response();
    }

    $done_uri = "/job/view/{$id}/";

    if ($request->isDialogFormPost()) {
      $editor = id(new PhabricatorJobPostingTechStackEditor())
        ->setActor($viewer)
        ->setRequest($request)
        ->setJobPosting($job)
        ->setContinueOnMissingFields(false)
        ->apply();

      return id(new AphrontRedirectResponse())->setURI($done_uri);
    }

    $course_datasource = id(new CoursepathItemDatasource());

    $current = $this->loadCurrentTechStack($job, $viewer);

    $coursepath_item_phid = null;
    if ($current->getCoursepathItemPHID()) {
      $coursepath_item_phid = array($current->getCoursepathItemPHID());
    }

    $form = id(new AphrontFormView())
      ->setUser($viewer)
      ->appendControl(
        id(new AphrontFormTokenizerControl())
          ->setLabel(pht('Coursepath'))
          ->setName('coursepathItemPHID')
          ->setLimit(1)
          ->setValue($coursepath_item_phid)
          ->setDatasource($course_datasource));

    $dialog = $this->newDialog()
      ->setTitle(pht('%s Tech Stack', $job->getName()))
      ->appendForm($form)
      ->addCancelButton($done_uri)
      ->addSubmitButton(pht('Save'));

    return $dialog;

  }

  private function loadCurrentTechStack(JobPosting $job , $viewer) {
    $app = id(new PhabricatorApplicationQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withClasses(array('PhabricatorProjectApplication'))
      ->executeOne();

    $view_policy = $app->getPolicy(
      PhabricatorPolicyCapability::CAN_VIEW);
    $edit_policy = $app->getPolicy(
      PhabricatorPolicyCapability::CAN_VIEW);

    $current_tech_stack = id(new JobPostingTechStack())->loadOneWhere(
      'postingPHID = %s',
      $job->getPHID());

    if ($current_tech_stack) {
      $current_tech_stack->setViewPolicy($view_policy);
      $current_tech_stack->setEditPolicy($edit_policy);
    } else {
      $current_tech_stack = JobPostingTechStack::initializeNewRspSpec(
                      $viewer, $job);
    }

    return $current_tech_stack;
  }
}
