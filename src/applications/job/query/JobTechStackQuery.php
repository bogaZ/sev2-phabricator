<?php

final class JobTechStackQuery
  extends PhabricatorCursorPagedPolicyAwareQuery {

  private $ids;
  private $postingPHIDs;

  public function withIDs(array $ids) {
    $this->ids = $ids;
    return $this;
  }

  public function withPostingPHIDs(array $phids) {
    $this->postingPHIDs = $phids;
    return $this;
  }

  public function newResultObject() {
    return new JobPostingTechStack();
  }

  protected function loadPage() {
    return $this->loadStandardPage($this->newResultObject());
  }

  protected function buildWhereClauseParts(AphrontDatabaseConnection $conn) {
    $where = parent::buildWhereClauseParts($conn);

    if ($this->ids !== null) {
      $where[] = qsprintf(
        $conn,
        'job_postingtechstack.id IN (%Ld)',
        $this->ids);
    }

    if ($this->postingPHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'job_postingtechstack.postingPHID IN (%Ls)',
        $this->postingPHIDs);
    }

    return $where;
  }

  public function getQueryApplicationClass() {
    return 'PhabricatorJobApplication';
  }

  protected function getPrimaryTableAlias() {
    return 'job_postingtechstack';
  }

}
