<?php

abstract class PhabricatorMentionDAO extends PhabricatorLiskDAO {

  public function getApplicationName() {
    return 'mention';
  }

}
