<?php

abstract class JobDAO extends PhabricatorLiskDAO {

  public function getApplicationName() {
    return 'job';
  }

}
