<?php

final class ConpherenceParticipantThreadUnreadQuery
  extends PhabricatorOffsetPagedQuery {

  private $participantPHIDs;
  private $unread;

  public function withParticipantPHIDs(array $phids) {
    $this->participantPHIDs = $phids;
    return $this;
  }

  public function execute() {
    $thread = new ConpherenceThread();
    $table = new ConpherenceParticipant();
    $conn = $table->establishConnection('r');

    $rows = queryfx_all(
      $conn,
      'SELECT conpherencePHID
        FROM %T participant JOIN %T thread
        ON participant.conpherencePHID = thread.phid %Q %Q %Q',
      $table->getTableName(),
      $thread->getTableName(),
      $this->buildWhereClause($conn),
      $this->buildGroupByClause($conn),
      $this->buildLimitClause($conn));

    return ipull($rows, 'conpherencePHID');
  }

  protected function buildWhereClause(AphrontDatabaseConnection $conn) {
    $where = array();

    if ($this->participantPHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'participant.participantPHID IN (%Ls)',
        $this->participantPHIDs);
    }

    $where[] = qsprintf(
      $conn,
      'participant.seenMessageCount < thread.messageCount');

    return $this->formatWhereClause($conn, $where);
  }

  private function buildGroupByClause(AphrontDatabaseConnection $conn) {
    return qsprintf(
      $conn,
      'GROUP BY conpherencePHID');
  }

}
