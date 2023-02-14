<?php

final class CoursepathItemTrackQuery
  extends CoursepathQuery {

  private $ids;
  private $phids;
  private $itemPHIDs = array();
  private $name;

  private $needItems;
  private $needEnrollments;

  public function withIDs(array $ids) {
    $this->ids = $ids;
    return $this;
  }

  public function withPHIDs(array $phids) {
    $this->phids = $phids;
    return $this;
  }

  public function withItemPHIDs(array $phids) {
    $this->itemPHIDs = $phids;
    return $this;
  }

  public function withName($name) {
    $this->name = $name;
    return $this;
  }

  public function needItems($need_item) {
    $this->needItems = $need_item;
    return $this;
  }

  public function needEnrollments($need_enrollments) {
    $this->needEnrollments = $need_enrollments;
    return $this;
  }

  private function shouldJoinItem() {
    return $this->itemPHIDs && !empty($this->itemPHIDs);
  }

  public function newResultObject() {
    return new CoursepathItemTrack();
  }

  protected function loadPage() {
    return $this->loadStandardPage($this->newResultObject());
  }

  protected function buildWhereClauseParts(AphrontDatabaseConnection $conn) {
    $where = parent::buildWhereClauseParts($conn);

    if ($this->ids !== null) {
      $where[] = qsprintf(
        $conn,
        'coursepath_itemtrack.id IN (%Ld)',
        $this->ids);
    }

    if ($this->phids !== null) {
      $where[] = qsprintf(
        $conn,
        'coursepath_itemtrack.phid IN (%Ls)',
        $this->phids);
    }

    if (count($this->itemPHIDs) > 0) {
      $where[] = qsprintf(
        $conn,
        'coursepath_itemtrack.itemPHID IN (%Ls)',
        $this->itemPHIDs);
    }

    if ($this->name !== null) {
      $where[] = qsprintf(
        $conn,
        'coursepath_itemtrack.name LIKE %>',
        $this->name);
    }

    return $where;
  }

  protected function getPrimaryTableAlias() {
    return 'coursepath_itemtrack';
  }

  protected function willFilterPage(array $tracks) {
    assert_instances_of($tracks, 'CoursepathItemTrack');

    $phids = mpull($tracks, 'getItemPHID');

    if ($this->needItems) {
      $items = id(new CoursepathItem())->loadAllWhere(
        'phid IN (%Ls)',
        $phids);

      $items = mgroup($items, 'getPHID');
      foreach ($tracks as $track) {
        $track->attachItems(idx($items, $track->getPHID(), array()));
      }
    }

    if ($this->needEnrollments) {
        $enrollments = id(new CoursepathItemEnrollment())->loadAllWhere(
          'itemPHID IN (%Ls)',
          $phids);

        $enrollments = mgroup($enrollments, 'getItemPHID');
        foreach ($tracks as $track) {
          $track->attachEnrollments(
            idx($enrollments,
            $track->getItemPHID(),
            array()));
        }
      }

    return $tracks;
  }

  protected function buildJoinClauseParts(AphrontDatabaseConnection $conn) {
    $join = parent::buildJoinClauseParts($conn);
    $items = new self();

    if ($this->shouldJoinItem()) {
      $join[] = qsprintf(
        $conn,
        'JOIN %T coursepath_item ON
        coursepath_itemtrack.itemPHID = coursepath_item.phid',
        id(new CoursepathItem())->getTableName());
    }

    return $join;
  }

}
