<?php

abstract class PhabricatorMentionController extends PhabricatorController {

  public function buildApplicationMenu() {
    return $this->newApplicationMenu()
      ->setSearchEngine(new PhabricatorMentionSearchEngine());
  }
}
