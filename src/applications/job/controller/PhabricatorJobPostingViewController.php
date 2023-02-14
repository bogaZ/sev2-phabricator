<?php

final class PhabricatorJobPostingViewController
  extends PhabricatorJobPostingDetailController {

  public function shouldAllowPublic() {
    return true;
  }

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $id = $request->getURIData('id');

    $item = id(new JobPostingQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->needTechStack(true)
      ->executeOne();
    if (!$item) {
      return new Aphront404Response();
    }

    $this->setItem($item);

    $crumbs = $this->buildApplicationCrumbs();
    $title = $item->getName();

    $header = $this->buildHeaderView();
    $curtain = $this->buildCurtain($item);
    $description = $this->buildDescriptionView($item);
    $benefit = $this->buildBenefitView($item);
    $perk = $this->buildPerkView($item);

    $timeline = $this->buildTransactionTimeline(
      $item,
      new JobPostingTransactionQuery());

    $comment_view = id(new PhabricatorJobPostingEditEngine())
      ->setViewer($viewer)
      ->buildEditEngineCommentView($item);

    $view = id(new PHUITwoColumnView())
      ->setHeader($header)
      ->setCurtain($curtain)
      ->setMainColumn(array(
          $timeline,
          $comment_view,
        ))
      ->addPropertySection(pht('Description'), $description)
      ->addPropertySection(pht('Benefits'), $benefit)
      ->addPropertySection(pht('Perks'), $perk);

    $navigation = $this->buildSideNavView('view');

    return $this->newPage()
      ->setTitle($title)
      ->setCrumbs($crumbs)
      ->setPageObjectPHIDs(array($item->getPHID()))
      ->setNavigation($navigation)
      ->appendChild($view);
  }

  private function buildDescriptionView(
    JobPosting $item) {
    $viewer = $this->getViewer();

    $view = id(new PHUIPropertyListView())
      ->setUser($viewer);

    $description = $item->getDescription();
    if (strlen($description)) {
      $view->addTextContent(
        new PHUIRemarkupView($viewer, $description));
    }

    return $view;
  }

  private function buildBenefitView(
    JobPosting $job) {
    $viewer = $this->getViewer();

    $view = id(new PHUIPropertyListView())
      ->setUser($viewer);

    $benefit = $job->getBenefit();
    if (strlen($benefit)) {
      $view->addTextContent(
        new PHUIRemarkupView($viewer, $benefit));
    }

    return $view;
  }

  private function buildPerkView(
    JobPosting $job) {
    $viewer = $this->getViewer();

    $view = id(new PHUIPropertyListView())
      ->setUser($viewer);

    $perk = $job->getPerk();
    if (strlen($perk)) {
      $view->addTextContent(
        new PHUIRemarkupView($viewer, $perk));
    }

    return $view;
  }

  private function buildCurtain(JobPosting $item) {
    $viewer = $this->getViewer();

    $can_edit = PhabricatorPolicyFilter::hasCapability(
      $viewer,
      $item,
      PhabricatorPolicyCapability::CAN_EDIT);

    $id = $item->getID();
    $edit_uri = $this->getApplicationURI("/edit/{$id}/");
    $archive_uri = $this->getApplicationURI("/state/{$id}/");

    $curtain = $this->newCurtainView($item);

    $curtain->addAction(
      id(new PhabricatorActionView())
        ->setName(pht('Edit Job Posting'))
        ->setIcon('fa-pencil')
        ->setDisabled(!$can_edit)
        ->setHref($edit_uri));

    if ($item->isArchived()) {
      $curtain->addAction(
        id(new PhabricatorActionView())
          ->setName(pht('Activate Job Posting'))
          ->setIcon('fa-check')
          ->setDisabled(!$can_edit)
          ->setWorkflow($can_edit)
          ->setHref($archive_uri));
    } else {
      $curtain->addAction(
        id(new PhabricatorActionView())
          ->setName(pht('Archive Job Posting'))
          ->setIcon('fa-ban')
          ->setDisabled(!$can_edit)
          ->setWorkflow($can_edit)
          ->setHref($archive_uri));
    }

    return $curtain;
  }

}
