<?php

final class CoursepathItemEnrollmentQuery
  extends PhabricatorCursorPagedPolicyAwareQuery {

  private $itemPHIDs;
  private $registrarPHIDs;
  private $tutorPHIDs;
  private $itemStatuses = null;

  protected function willFilterPage(array $enrollments) {
    $item_phids = array();
    foreach ($enrollments as $key => $enroll) {
      $item_phids[] = $enroll->getItemPHID();
    }

    $items = id(new CoursepathItemQuery())
      ->setViewer($this->getViewer())
      ->withPHIDs($item_phids)
      ->execute();

    $items = mpull($items, null, 'getPHID');
    foreach ($enrollments as $key => $enroll) {
      $enroll_item = idx($items, $enroll->getItemPHID());
      if (!$enroll_item) {
        unset($enrollments[$key]);
        $this->didRejectResult($enroll);
        continue;
      }
      $enroll->attachItem($enroll_item);
    }

    return $enrollments;
  }

  public function withItemPHIDs(array $phids) {
    $this->itemPHIDs = $phids;
    return $this;
  }

  public function withRegistrarPHIDs(array $phids) {
    $this->registrarPHIDs = $phids;
    return $this;
  }

  public function withTutorPHIDs(array $phids) {
    $this->tutorPHIDs = $phids;
    return $this;
  }

  public function withItemStatuses(array $statuses) {
    $this->itemStatuses = $statuses;
    return $this;
  }

  private function shouldJoinItem() {
    return (bool)$this->itemStatuses;
  }

  protected function loadPage() {
    return $this->loadStandardPage($this->newResultObject());
  }

  public function newResultObject() {
    return new CoursepathItemEnrollment();
  }

  public function getTableName() {
    return sev2table('coursepath_itemenrollment');
  }

  protected function getPrimaryTableAlias() {
    return 'coursepath_itemenrollment';
  }

  protected function buildWhereClauseParts(AphrontDatabaseConnection $conn) {
    $where = parent::buildWhereClauseParts($conn);

    if ($this->itemPHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'coursepath_itemenrollment.itemPHID IN (%Ls)',
        $this->itemPHIDs);
    }

    if ($this->registrarPHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'coursepath_itemenrollment.registrarPHID IN (%Ls)',
        $this->registrarPHIDs);
    }

    if ($this->tutorPHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'coursepath_itemenrollment.tutorPHID IN (%Ls)',
        $this->tutorPHIDs);
    }

    if ($this->itemStatuses !== null) {
      $where[] = qsprintf(
        $conn,
        'coursepath_item.status IN (%Ls)',
        $this->itemStatuses);
    }


    return $where;
  }

  protected function buildJoinClauseParts(AphrontDatabaseConnection $conn) {
    $join = parent::buildJoinClauseParts($conn);
    $items = new CoursepathItemQuery();

    if ($this->shouldJoinItem()) {
      $join[] = qsprintf(
        $conn,
        'JOIN %T coursepath_item ON coursepath_itemenrollment.itemPHID = coursepath_item.phid',
        $items->getTableName());
    }

    return $join;
  }

  public function getQueryApplicationClass() {
    return 'PhabricatorCoursepathApplication';
  }

}
