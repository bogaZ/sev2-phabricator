<?php

final class PhabricatorJobPostingListController
  extends PhabricatorJobPostingController {

  public function handleRequest(AphrontRequest $request) {
    return id(new JobPostingSearchEngine())
      ->setController($this)
      ->buildResponse();
  }

  protected function buildApplicationCrumbs() {
    $crumbs = parent::buildApplicationCrumbs();

    id(new PhabricatorJobPostingEditEngine())
      ->setViewer($this->getViewer())
      ->addActionToCrumbs($crumbs);

    return $crumbs;
  }
}
