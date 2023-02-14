<?php

abstract class PhabricatorCoursepathItemController
  extends PhabricatorCoursepathController {

  public function buildApplicationMenu() {
    return $this->newApplicationMenu()
      ->setSearchEngine(new CoursepathItemSearchEngine());
  }

}
