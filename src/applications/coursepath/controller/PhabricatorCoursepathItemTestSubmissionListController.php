<?php

final class PhabricatorCoursepathItemTestSubmissionListController
  extends PhabricatorCoursepathItemTestSubmissionController {

  public function handleRequest(AphrontRequest $request) {
    return id(new CoursepathItemTestSubmissionSearchEngine())
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
    $crumbs = parent::buildApplicationCrumbs();
    $crumbs->addTextCrumb($item->getName(), $uri);

    return $crumbs;
  }
}
