<?php

final class PhabricatorRepositoryBuildInfoFileQuery
  extends PhabricatorCursorPagedPolicyAwareQuery {

  private $ids;
  private $phids;
  private $filename;
  private $buildPHIDs;
  private $filePHIDs;

  public function withIDs(array $ids) {
    $this->ids = $ids;
    return $this;
  }

  public function withPHIDs(array $phids) {
    $this->phids = $phids;
    return $this;
  }

  public function withFilename($filename) {
    $this->filename = $filename;
    return $this;
  }

  public function withBuildPHIDs(array $build) {
    $this->buildPHIDs = $build;
    return $this;
  }

  public function withFilePHIDs(array $file) {
    $this->filePHIDs = $file;
    return $this;
  }

  public function newResultObject() {
    return new PhabricatorRepositoryBuildInfoFile();
  }

  protected function getPrimaryTableAlias() {
     return 'buildinfofile';
  }

  protected function loadPage() {
    return $this->loadStandardPage($this->newResultObject());
  }

  protected function buildWhereClauseParts(AphrontDatabaseConnection $conn) {
    $where = parent::buildWhereClauseParts($conn);

    if ($this->buildPHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'buildinfofile.buildPHID IN (%Ls)',
        $this->buildPHIDs);
    }

    if ($this->filePHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'buildinfofile.filePHID IN (%Ls)',
        $this->filePHIDs);
    }

    if ($this->filename !== null) {
      $where[] = qsprintf(
        $conn,
        'buildinfofile.filename = %s',
        $this->filename);
    }

    return $where;
  }

  public function getQueryApplicationClass() {
    return 'PhabricatorDiffusionApplication';
  }

}
