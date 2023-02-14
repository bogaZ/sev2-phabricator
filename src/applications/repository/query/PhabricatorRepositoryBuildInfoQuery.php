<?php

final class PhabricatorRepositoryBuildInfoQuery
  extends PhabricatorCursorPagedPolicyAwareQuery {

  private $ids;
  private $phids;
  private $repositoryPHIDs;
  private $configurations;

  public function withIDs(array $ids) {
    $this->ids = $ids;
    return $this;
  }

  public function withPHIDs(array $phids) {
    $this->phids = $phids;
    return $this;
  }

  public function withRepositoryPHIDs(array $repository) {
    $this->repositoryPHIDs = $repository;
    return $this;
  }

  public function newResultObject() {
    return new PhabricatorRepositoryBuildInfo();
  }

  protected function getPrimaryTableAlias() {
     return 'buildinfo';
  }

  protected function loadPage() {
    return $this->loadStandardPage($this->newResultObject());
  }

  protected function buildWhereClauseParts(AphrontDatabaseConnection $conn) {
    $where = parent::buildWhereClauseParts($conn);

    if ($this->ids !== null) {
      $where[] = qsprintf(
        $conn,
        'buildinfo.id IN (%Ls)',
        $this->ids);
    }

    if ($this->repositoryPHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'buildinfo.repositoryPHID IN (%Ls)',
        $this->repositoryPHIDs);
    }

    return $where;
  }

  public function getQueryApplicationClass() {
    return 'PhabricatorDiffusionApplication';
  }

}
