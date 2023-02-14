<?php

abstract class CoursepathQuery extends PhabricatorCursorPagedPolicyAwareQuery {

  public function getQueryApplicationClass() {
    return 'PhabricatorCoursepathApplication';
  }

}
