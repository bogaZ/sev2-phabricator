<?php

final class PhabricatorProjectColumnPositionQuery
  extends PhabricatorCursorPagedPolicyAwareQuery {

  private $ids;
  private $boardPHIDs;
  private $objectPHIDs;
  private $columnPHIDs;

  private $needManiphests;

  public function withIDs(array $ids) {
    $this->ids = $ids;
    return $this;
  }

  public function withBoardPHIDs(array $board_phids) {
    $this->boardPHIDs = $board_phids;
    return $this;
  }

  public function withObjectPHIDs(array $object_phids) {
    $this->objectPHIDs = $object_phids;
    return $this;
  }

  public function withColumnPHIDs(array $column_phids) {
    $this->columnPHIDs = $column_phids;
    return $this;
  }

  public function needManiphests($need_maniphest) {
    $this->needManiphests = $need_maniphest;
    return $this;
  }

  public function newResultObject() {
    return new PhabricatorProjectColumnPosition();
  }

  protected function loadPage() {
    return $this->loadStandardPage($this->newResultObject());
  }

  protected function willFilterPage(array $page) {
    $object_phids = mpull($page, 'getObjectPHID');

    if ($this->needManiphests) {
      $maniphests = id(new ManiphestTaskQuery())
        ->setViewer($this->getViewer())
        ->withPHIDs($object_phids)
        ->execute();

      $maniphests = mgroup($maniphests, 'getPHID');
      if ($maniphests) {
        foreach ($page as $column_position) {
          $column_position->attachManiphests(
            idx($maniphests, $column_position->getObjectPHID(), array()));
        }
      }
    }

    return $page;
  }

  protected function buildWhereClauseParts(AphrontDatabaseConnection $conn) {
    $where = array();

    if ($this->ids !== null) {
      $where[] = qsprintf(
        $conn,
        'id IN (%Ld)',
        $this->ids);
    }

    if ($this->boardPHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'boardPHID IN (%Ls)',
        $this->boardPHIDs);
    }

    if ($this->objectPHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'objectPHID IN (%Ls)',
        $this->objectPHIDs);
    }

    if ($this->columnPHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'columnPHID IN (%Ls)',
        $this->columnPHIDs);
    }

    return $where;
  }

  public function getQueryApplicationClass() {
    return 'PhabricatorProjectApplication';
  }

}
