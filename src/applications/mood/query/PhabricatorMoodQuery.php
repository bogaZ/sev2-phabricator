<?php

final class PhabricatorMoodQuery
  extends PhabricatorCursorPagedPolicyAwareQuery {

  private $ids;
  private $moodPHIDs;
  private $userPHIDs;
  private $mood;
  private $startDate;
  private $endDate;
  private $isForDev;

  public function withIDs(array $ids) {
    $this->ids = $ids;
    return $this;
  }

  public function withPHIDs(array $mood_phids) {
    $this->moodPHIDs = $mood_phids;
    return $this;
  }

  public function withUserPHIDs(array $user_phids) {
    $this->userPHIDs = $user_phids;
    return $this;
  }

  public function withMood(array $mood) {
    $this->mood = $mood;
    return $this;
  }

  public function withStartDate($start_date) {
    $this->startDate = $start_date;
    return $this;
  }

  public function withEndDate($end_date) {
    $this->endDate = $end_date;
    return $this;
  }

  public function withIsForDev($is_for_dev) {
    $this->isForDev = $is_for_dev;
    return $this;
  }

  protected function loadPage() {
    return $this->loadStandardPage($this->newResultObject());
  }

  public function newResultObject() {
    return new PhabricatorMood();
  }

  protected function buildWhereClauseParts(AphrontDatabaseConnection $conn) {
    $where = parent::buildWhereClauseParts($conn);


    if ($this->ids !== null) {
      $where[] = qsprintf(
        $conn,
        'mood.id IN (%Ld)',
        $this->ids);
    }

    if ($this->moodPHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'mood.phid IN (%Ls)',
        $this->moodPHIDs);
    }

    if ($this->userPHIDs) {
      $where[] = qsprintf(
        $conn,
        'mood.userPHID IN (%Ls)',
        $this->userPHIDs);
    }

    if ($this->mood) {
      $where[] = qsprintf(
        $conn,
        'mood.mood IN (%Ls)',
        $this->mood);
    }

    if ($this->startDate) {
      $where[] = qsprintf(
        $conn,
        'mood.dateCreated >= %s',
        $this->startDate);
    }

    if ($this->endDate) {
      $where[] = qsprintf(
        $conn,
        'mood.dateCreated <= %s',
        $this->endDate);
    }

    if ($this->isForDev !== null) {
      if ($this->isForDev) {
        $where[] = qsprintf(
          $conn,
          'mood.isForDev = 1');
      } else {
        $where[] = qsprintf(
          $conn,
          'mood.isForDev = 0');
      }
    }

    return $where;
  }

  public function getQueryApplicationClass() {
    return 'PhabricatorMoodApplication';
  }

  public function getOrderableColumns() {
    return array(
      'dateCreated' => array(
        'table' => $this->getPrimaryTableAlias(),
        'column' => 'dateCreated',
        'type' => 'int',
      ),
    ) + parent::getOrderableColumns();
  }

  protected function newPagingMapFromPartialObject($object) {
    return array(
      'id' => (int)$object->getID(),
      'dateCreated' => (int)$object->getStartDate(),
    );
  }

  public function getTableName() {
    return sev2table('mood');
  }

  protected function getPrimaryTableAlias() {
    return 'mood';
  }
}
