<?php

final class PhabricatorMentionQuery
  extends PhabricatorCursorPagedPolicyAwareQuery {

  private $ids;
  private $mentionPHIDs;
  private $callerPHID;
  private $createdAfter;
  private $createdBefore;

  public function withIDs(array $ids) {
    $this->ids = $ids;
    return $this;
  }

  public function withMentionPHIDs(array $mention_phids) {
    $this->mentionPHIDs = $mention_phids;
    return $this;
  }

  public function withCallerPHID(array $caller_phid) {
    $this->callerPHID = $caller_phid;
    return $this;
  }

  public function withStartDate($start_date) {
    $this->createdAfter = $start_date;
    return $this;
  }

  public function withEndDate($end_date) {
    $this->createdBefore = $end_date;
    return $this;
  }

  protected function loadPage() {
    return $this->loadStandardPage($this->newResultObject());
  }

  public function newResultObject() {
    return new PhabricatorMention();
  }

  protected function buildJoinClauseParts(AphrontDatabaseConnection $conn) {
    $joins = parent::buildJoinClauseParts($conn);

    if ($this->shouldJoinTable()) {
      $joins[] = qsprintf(
        $conn,
        'JOIN %T mentioned_mention
         ON mentioned_mention.mentionID = mention.id',
        id(new PhabricatorMentionMentioned())->getTableName());
    }

    return $joins;
  }

  private function shouldJoinTable() {
    if ($this->mentionPHIDs != null) {
      return true;
    }
    return false;
  }

  protected function buildSelectClauseParts(AphrontDatabaseConnection $conn) {
    $select = parent::buildSelectClauseParts($conn);

    if ($this->shouldJoinTable()) {
      $select[] = qsprintf($conn,
        'mentioned_mention.userPHID,
        mentioned_mention.mentionID');
    }
    return $select;
  }

  protected function buildWhereClauseParts(AphrontDatabaseConnection $conn) {
    $where = parent::buildWhereClauseParts($conn);

    if ($this->ids !== null) {
      $where[] = qsprintf(
        $conn,
        'mention.id IN (%Ld)',
        $this->ids);
    }

    if ($this->mentionPHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'mentioned_mention.userPHID IN (%Ls)',
        $this->mentionPHIDs);
    }

    if ($this->callerPHID) {
      $where[] = qsprintf(
        $conn,
        'mention.callerPHID IN (%Ls)',
        $this->callerPHID);
    }

    if ($this->createdAfter) {
      $where[] = qsprintf(
        $conn,
        'mention.dateCreated >= %s',
        $this->createdAfter);
    }

    if ($this->createdBefore) {
      $where[] = qsprintf(
        $conn,
        'mention.dateCreated <= %s',
        $this->createdBefore);
    }

    return $where;
  }

  public function getQueryApplicationClass() {
    return 'PhabricatorMentionApplication';
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
      'dateCreated' => (int)$object->getDateCreated(),
    );
  }

  public function getTableName() {
    return sev2table('mention');
  }

  protected function getPrimaryTableAlias() {
    return 'mention';
  }
}
