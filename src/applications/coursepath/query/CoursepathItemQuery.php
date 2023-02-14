<?php

final class CoursepathItemQuery
  extends CoursepathQuery {

  private $ids;
  private $phids;
  private $registrarPHIDs = array();
  private $statuses;
  private $slug;

  private $needEnrollments;
  private $needTracks;

  public function withIDs(array $ids) {
    $this->ids = $ids;
    return $this;
  }

  public function withPHIDs(array $phids) {
    $this->phids = $phids;
    return $this;
  }

  public function withRegistrarPHIDs(array $phids) {
    $this->registrarPHIDs = $phids;
    return $this;
  }

  public function withStatuses(array $statuses) {
    $this->statuses = $statuses;
    return $this;
  }

  public function withSlug($slug) {
    $this->slug = $slug;
    return $this;
  }

  public function needTracks($need_tracks) {
    $this->needTracks = $need_tracks;
    return $this;
  }

  public function needEnrollments($need_enrollments) {
    $this->needEnrollments = $need_enrollments;
    return $this;
  }

  public function withNameNgrams($ngrams) {
    return $this->withNgramsConstraint(
      id(new CoursepathItemNameNgrams()),
      $ngrams);
  }

  private function shouldJoinEnroll() {
    return $this->registrarPHIDs && !empty($this->registrarPHIDs);
  }

  public function newResultObject() {
    return new CoursepathItem();
  }

  protected function loadPage() {
    return $this->loadStandardPage($this->newResultObject());
  }

  protected function willFilterPage(array $items) {
    assert_instances_of($items, 'CoursepathItem');

    $phids = mpull($items, 'getPHID');

    if ($this->needTracks) {
      $tracks = id(new CoursepathItemTrack())->loadAllWhere(
        'itemPHID IN (%Ls)',
        $phids);

      $tracks = mgroup($tracks, 'getItemPHID');
      foreach ($items as $item) {
        $item->attachTracks(idx($tracks, $item->getPHID(), array()));
      }
    }

    if ($this->needEnrollments) {
      $enrollments = id(new CoursepathItemEnrollment())->loadAllWhere(
        'itemPHID IN (%Ls)',
        $phids);

      $enrollments = mgroup($enrollments, 'getItemPHID');
      foreach ($items as $item) {
        $item->attachEnrollments(idx($enrollments, $item->getPHID(), array()));
      }
    }

    return $items;
  }

  protected function buildWhereClauseParts(AphrontDatabaseConnection $conn) {
    $where = parent::buildWhereClauseParts($conn);

    if ($this->ids !== null) {
      $where[] = qsprintf(
        $conn,
        'coursepath_item.id IN (%Ld)',
        $this->ids);
    }

    if ($this->phids !== null) {
      $where[] = qsprintf(
        $conn,
        'coursepath_item.phid IN (%Ls)',
        $this->phids);
    }

    if ($this->statuses !== null) {
      $where[] = qsprintf(
        $conn,
        'coursepath_item.status IN (%Ls)',
        $this->statuses);
    }

    if (count($this->registrarPHIDs) > 0) {
      $where[] = qsprintf(
        $conn,
        'coursepath_itemenrollment.registrarPHID IN (%Ls)',
        $this->registrarPHIDs);
    }

    if ($this->slug !== null) {
      $where[] = qsprintf(
        $conn,
        'coursepath_item.slug = %s',
        $this->slug);
    }

    return $where;
  }

  protected function getPrimaryTableAlias() {
    return 'coursepath_item';
  }

  protected function buildJoinClauseParts(AphrontDatabaseConnection $conn) {
    $join = parent::buildJoinClauseParts($conn);
    $enrollments = new CoursepathItemEnrollmentQuery();

    if ($this->shouldJoinEnroll()) {
      $join[] = qsprintf(
        $conn,
        'JOIN %T coursepath_itemenrollment ON coursepath_item.phid = coursepath_itemenrollment.itemPHID',
        $enrollments->getTableName());
    }

    return $join;
  }

}
