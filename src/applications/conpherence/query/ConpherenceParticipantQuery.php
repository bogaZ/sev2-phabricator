<?php

final class ConpherenceParticipantQuery extends PhabricatorOffsetPagedQuery {

  private $participantPHIDs;
  private $conpherencePHIDs;

  public function withParticipantPHIDs(array $phids) {
    $this->participantPHIDs = $phids;
    return $this;
  }

  public function withConpherencePHIDs(array $phids) {
    $this->conpherencePHIDs = $phids;
    return $this;
  }

  public function execute() {
    $table = new ConpherenceParticipant();
    $thread = new ConpherenceThread();

    $conn = $table->establishConnection('r');

    $data = queryfx_all(
      $conn,
      'SELECT participantPHID, conpherencePHID,
          thread.dateCreated, thread.dateModified,
          thread.title FROM %T participant JOIN %T thread
        ON participant.conpherencePHID = thread.phid %Q %Q %Q',
      $table->getTableName(),
      $thread->getTableName(),
      $this->buildWhereClause($conn),
      $this->buildOrderClause($conn),
      $this->buildLimitClause($conn));

    return $table->loadAllFromArray($data);
  }

  protected function buildWhereClause(AphrontDatabaseConnection $conn) {
    $where = array();

    if ($this->participantPHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'participantPHID IN (%Ls)',
        $this->participantPHIDs);
    }

    if ($this->conpherencePHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'conpherencePHID IN (%Ls)',
        $this->conpherencePHIDs);
    }

    return $this->formatWhereClause($conn, $where);
  }

  private function buildOrderClause(AphrontDatabaseConnection $conn) {

    if ($this->conpherencePHIDs !== null) {
      return qsprintf(
        $conn,
        'ORDER BY participant.conpherencePHID DESC');
    }

    return qsprintf(
      $conn,
      'ORDER BY thread.title ASC, thread.id DESC, participant.id DESC');
  }

}
