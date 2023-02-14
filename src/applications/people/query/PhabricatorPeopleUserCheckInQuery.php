<?php

final class PhabricatorPeopleUserCheckInQuery
  extends PhabricatorCursorPagedPolicyAwareQuery {

  private $ids;
  private $phids;

  private $dateCreatedAfter;
  private $dateCreatedBefore;

  public function withIDs(array $ids) {
    $this->ids = $ids;
    return $this;
  }

  public function withPHIDs(array $phids) {
    $this->phids = $phids;
    return $this;
  }

  public function withDateCreatedBefore($date_created_before) {
    $this->dateCreatedBefore = $date_created_before;
    return $this;
  }

  public function withDateCreatedAfter($date_created_after) {
    $this->dateCreatedAfter = $date_created_after;
    return $this;
  }

  public function newResultObject() {
    return new PhabricatorUserCheckIn();
  }

  protected function loadPage() {
    return $this->loadStandardPage(new PhabricatorUserCheckIn());
  }

  protected function didFilterPage(array $pastes) {
    return $pastes;
  }

  protected function buildWhereClauseParts(AphrontDatabaseConnection $conn) {
    $where = parent::buildWhereClauseParts($conn);

    if ($this->ids !== null) {
      $where[] = qsprintf(
        $conn,
        'id IN (%Ld)',
        $this->ids);
    }

    if ($this->phids !== null) {
      $where[] = qsprintf(
        $conn,
        'phid IN (%Ls)',
        $this->phids);
    }

    if ($this->dateCreatedAfter !== null) {
      $where[] = qsprintf(
        $conn,
        'dateCreated >= %d',
        $this->dateCreatedAfter);
    }

    if ($this->dateCreatedBefore !== null) {
      $where[] = qsprintf(
        $conn,
        'dateCreated <= %d',
        $this->dateCreatedBefore);
    }


    return $where;
  }

  public function getQueryApplicationClass() {
    return 'PhabricatorPeopleApplication';
  }

}
