<?php

abstract class PhabricatorJobPostingController
  extends PhabricatorJobController {

  public function buildApplicationMenu() {
    return $this->newApplicationMenu()
      ->setSearchEngine(new JobPostingSearchEngine());
  }

}
