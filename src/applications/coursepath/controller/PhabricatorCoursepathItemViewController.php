<?php

final class PhabricatorCoursepathItemViewController
  extends PhabricatorCoursepathItemDetailController {

  public function shouldAllowPublic() {
    return true;
  }

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $id = $request->getURIData('id');

    $item = id(new CoursepathItemQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->needTracks(true)
      ->executeOne();
    if (!$item) {
      return new Aphront404Response();
    }

    $this->setItem($item);

    $crumbs = $this->buildApplicationCrumbs();
    $title = $item->getName();

    $header = $this->buildHeaderView();
    $curtain = $this->buildCurtain($item);
    $details = $this->buildDetailsView($item);
    $track_name = $this->buildTrackView($item->getTracks());

    $timeline = $this->buildTransactionTimeline(
      $item,
      new CoursepathItemTransactionQuery());

    $comment_view = id(new PhabricatorCoursepathItemEditEngine())
      ->setViewer($viewer)
      ->buildEditEngineCommentView($item);

    $view = id(new PHUITwoColumnView())
      ->setHeader($header)
      ->setCurtain($curtain)
      ->setMainColumn(array(
          $timeline,
          $comment_view,
        ))
      ->addPropertySection(pht('Description'), $details);

    $navigation = $this->buildSideNavView('view');

    return $this->newPage()
      ->setTitle($title)
      ->setCrumbs($crumbs)
      ->setPageObjectPHIDs(array($item->getPHID()))
      ->setNavigation($navigation)
      ->appendChild($view);
  }

  private function buildDetailsView(
    CoursepathItem $item) {
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


  private function buildTrackView(array $tracks) {
    $viewer = $this->getViewer();

    $view = id(new PHUIPropertyListView())
      ->setUser($viewer);

    foreach ($tracks as $track) {
      if (strlen($track->getName())) {
        $view->addTextContent(
          new PHUIRemarkupView($viewer,
           pht('- %s', $track->getName())));
      }
    }

    return $view;
  }

  private function buildCurtain(CoursepathItem $item) {
    $viewer = $this->getViewer();

    $can_edit = PhabricatorPolicyFilter::hasCapability(
      $viewer,
      $item,
      PhabricatorPolicyCapability::CAN_EDIT);

    $id = $item->getID();
    $edit_uri = $this->getApplicationURI("/item/edit/{$id}/");
    $archive_uri = $this->getApplicationURI("/item/archive/{$id}/");

    $curtain = $this->newCurtainView($item);

    $curtain->addAction(
      id(new PhabricatorActionView())
        ->setName(pht('Edit Course Path'))
        ->setIcon('fa-pencil')
        ->setDisabled(!$can_edit)
        ->setHref($edit_uri));

    if ($item->isArchived()) {
      $curtain->addAction(
        id(new PhabricatorActionView())
          ->setName(pht('Activate Course Path'))
          ->setIcon('fa-check')
          ->setDisabled(!$can_edit)
          ->setWorkflow($can_edit)
          ->setHref($archive_uri));
    } else {
      $curtain->addAction(
        id(new PhabricatorActionView())
          ->setName(pht('Archive Course Path'))
          ->setIcon('fa-ban')
          ->setDisabled(!$can_edit)
          ->setWorkflow($can_edit)
          ->setHref($archive_uri));
    }

    return $curtain;
  }

}
