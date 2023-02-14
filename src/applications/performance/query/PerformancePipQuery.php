<?php

final class PerformancePipQuery
  extends PhabricatorCursorPagedPolicyAwareQuery {

  private $ids;
  private $phids;
  private $ownerPHIDs;
  private $targetPHIDs;
  private $isActive;

  public function withIDs(array $ids) {
    $this->ids = $ids;
    return $this;
  }

  public function withPHIDs(array $phids) {
    $this->phids = $phids;
    return $this;
  }

  public function withIsActive($active) {
    $this->isActive = $active;
    return $this;
  }

  public function withOwnerPHIDs(array $owner_phids) {
    $this->ownerPHIDs = $owner_phids;
    return $this;
  }

  public function withTargetPHIDs(array $acc_phids) {
    $this->targetPHIDs = $acc_phids;
    return $this;
  }

  public function newResultObject() {
    return new PerformancePip();
  }

  protected function loadPage() {
    return $this->loadStandardPage($this->newResultObject());
  }

  protected function getPrimaryTableAlias() {
    return 'pip';
  }

  protected function buildWhereClauseParts(AphrontDatabaseConnection $conn) {
    $where = parent::buildWhereClauseParts($conn);

    if ($this->ids !== null) {
      $where[] = qsprintf(
        $conn,
        'pip.id IN (%Ld)',
        $this->ids);
    }

    if ($this->phids !== null) {
      $where[] = qsprintf(
        $conn,
        'pip.phid IN (%Ls)',
        $this->phids);
    }

    if ($this->ownerPHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'pip.ownerPHID IN (%Ls)',
        $this->ownerPHIDs);
    }

    if ($this->targetPHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'pip.targetPHID IN (%Ls)',
        $this->targetPHIDs);
    }

    if ($this->isActive !== null) {
      $where[] = qsprintf(
        $conn,
        'pip.isActive = %d',
        (int)$this->isActive);
    }

    return $where;
  }

  public function getQueryApplicationClass() {
    return 'PhabricatorPerformanceApplication';
  }

}
