<?php

final class SuiteStatisticQuery
  extends PhabricatorCursorPagedPolicyAwareQuery {

  public function newResultObject() {
    return new SuiteNotification();
  }

  protected function loadPage() {
    return $this->loadStandardPage($this->newResultObject());
  }

  public function getQueryApplicationClass() {
    return 'PhabricatorSuiteApplication';
  }

}
