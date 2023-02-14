<?php

final class PhabricatorCoursepathItemListController
  extends PhabricatorCoursepathItemController {

  public function handleRequest(AphrontRequest $request) {
    return id(new CoursepathItemSearchEngine())
      ->setController($this)
      ->buildResponse();
  }

  protected function buildApplicationCrumbs() {
    $crumbs = parent::buildApplicationCrumbs();

    id(new PhabricatorCoursepathItemEditEngine())
      ->setViewer($this->getViewer())
      ->addActionToCrumbs($crumbs);

    return $crumbs;
  }
}
