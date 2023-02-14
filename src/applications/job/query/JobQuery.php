<?php

abstract class JobQuery extends PhabricatorCursorPagedPolicyAwareQuery {

  public function getQueryApplicationClass() {
    return 'PhabricatorJobApplication';
  }

}
