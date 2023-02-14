<?php

final class PhabricatorCoursepathItemTrackListController
  extends PhabricatorCoursepathItemTestSubmissionController {

  public function handleRequest(AphrontRequest $request) {
    return id(new CoursepathItemTrackSearchEngine())
      ->setController($this)
      ->buildResponse();
  }

  protected function buildApplicationCrumbs() {
    $id = $this->getRequest()->getURIData('id');
    $item = id(new CoursepathItemQuery())
        ->setViewer($this->getViewer())
        ->withIDs(array($id))
        ->executeOne();

    $uri = '/coursepath/item/view/'.$item->getID().'/';

    $create_uri = $uri.'tracks/submit';
    $crumbs = parent::buildApplicationCrumbs();
    $crumbs->addTextCrumb($item->getName(), $uri);
    $crumbs->addAction(
        id(new PHUIListItemView())
          ->setName(pht('Add Teachable Course'))
          ->setIcon('fa-plus')
          ->setHref($create_uri));

    return $crumbs;
  }
}
