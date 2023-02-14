<?php

abstract class PhabricatorCoursepathItemTrackController
  extends PhabricatorCoursepathController {

  public function buildApplicationMenu() {
    return $this->newApplicationMenu()
      ->setSearchEngine(new CoursepathItemTrackSearchEngine());
  }

}
