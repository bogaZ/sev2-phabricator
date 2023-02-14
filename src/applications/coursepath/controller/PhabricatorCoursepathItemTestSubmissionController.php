<?php

abstract class PhabricatorCoursepathItemTestSubmissionController
  extends PhabricatorCoursepathController {

  public function buildApplicationMenu() {
    return $this->newApplicationMenu()
      ->setSearchEngine(new CoursepathItemTestSubmissionSearchEngine());
  }

}
