<?php

final class PhabricatorProjectRspSpecQuery
  extends PhabricatorCursorPagedPolicyAwareQuery {

  private $ids;
  private $projectPHIDs;
  private $billingUserPHIDs;

  public function withIDs(array $ids) {
    $this->ids = $ids;
    return $this;
  }

  public function withProjectPHIDs(array $phids) {
    $this->projectPHIDs = $phids;
    return $this;
  }

  public function withBillingUserPHIDs(array $phids) {
    $this->billingUserPHIDs = $phids;
    return $this;
  }

  public function newResultObject() {
    return new PhabricatorProjectRspSpec();
  }

  protected function loadPage() {
    return $this->loadStandardPage($this->newResultObject());
  }

  protected function buildWhereClauseParts(AphrontDatabaseConnection $conn) {
    $where = parent::buildWhereClauseParts($conn);

    if ($this->ids !== null) {
      $where[] = qsprintf(
        $conn,
        'rspspec.id IN (%Ld)',
        $this->ids);
    }

    if ($this->projectPHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'rspspec.projectPHID IN (%Ls)',
        $this->projectPHIDs);
    }

    if ($this->billingUserPHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'rspspec.billingUserPHID IN (%Ls)',
        $this->billingUserPHIDs);
    }

    return $where;
  }

  public function getQueryApplicationClass() {
    return 'PhabricatorProjectApplication';
  }

  protected function getPrimaryTableAlias() {
    return 'rspspec';
  }

}
