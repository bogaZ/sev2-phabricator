<?php

abstract class PhabricatorMoodController extends PhabricatorController {

  public function buildApplicationMenu() {
    return $this->newApplicationMenu()
      ->setSearchEngine(new PhabricatorMoodSearchEngine());
  }
}
