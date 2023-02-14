<?php

final class PhabricatorMentionMentionedQuery
  extends PhabricatorCursorPagedPolicyAwareQuery {

  private $id;
  private $mentionID;
  private $userPHID;

  public function withID(string $id) {
    $this->id = $id;
    return $this;
  }
  public function withMentionID(array $mention_id) {
    $this->mentionID = $mention_id;
    return $this;
  }
  public function withUserPHID(array $user_phid) {
    $this->userPHID = $user_phid;
    return $this;
  }

  protected function loadPage() {
    return $this->loadStandardPage($this->newResultObject());
  }

  protected function buildWhereClauseParts(AphrontDatabaseConnection $conn) {
    $where = parent::buildWhereClauseParts($conn);

    if ($this->id !== null) {
      $where[] = qsprintf(
        $conn,
        'mentioned_mention.mentionID IN (%d)',
        $this->id);
    }

    return $where;
  }

  public function getQueryApplicationClass() {
    return 'PhabricatorMentionApplication';
  }

  public function newResultObject() {
    return new PhabricatorMentionMentioned();
  }

  public function getTableName() {
    return sev2table('mentioned_mention');
  }

  protected function getPrimaryTableAlias() {
    return 'mentioned_mention';
  }
}
