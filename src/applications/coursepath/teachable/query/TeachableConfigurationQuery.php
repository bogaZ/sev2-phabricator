<?php

final class TeachableConfigurationQuery
  extends CoursepathQuery {

  private $ids;
  private $phids;
  private $creatorPHIDs;

  public function withIDs(array $ids) {
    $this->ids = $ids;
    return $this;
  }

  public function withPHIDs(array $phids) {
    $this->phids = $phids;
    return $this;
  }

  public function withCreatorPHIDs(array $phids) {
    $this->creatorPHIDs = $phids;
    return $this;
  }

  public function newResultObject() {
    return new TeachableConfiguration();
  }

  protected function loadPage() {
    return $this->loadStandardPage($this->newResultObject());
  }

  protected function buildWhereClauseParts(AphrontDatabaseConnection $conn) {
    $where = parent::buildWhereClauseParts($conn);

    if ($this->ids !== null) {
      $where[] = qsprintf(
        $conn,
        'coursepath_teachable.id IN (%Ld)',
        $this->ids);
    }

    if ($this->phids !== null) {
      $where[] = qsprintf(
        $conn,
        'coursepath_teachable.phid IN (%Ls)',
        $this->phids);
    }

    if ($this->creatorPHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'coursepath_teachable.creatorPHID IN (%Ls)',
        $this->creatorPHIDs);
    }

    return $where;
  }

  protected function getPrimaryTableAlias() {
    return 'coursepath_teachable';
  }

  public function getTableName() {
    return sev2table('coursepath_teachable');
  }

}
