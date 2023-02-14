<?php

final class JobInviteQuery
  extends JobQuery {

  protected $postingPHIDs;
  protected $applicantPHIDs = array();
  protected $inviterPHIDs;
  protected $statuses = null;

  protected function willFilterPage(array $applicants) {
    $posting_phids = array();
    foreach ($applicants as $key => $apply) {
      $posting_phids[] = $apply->getPostingPHID();
    }

    $postings = id(new JobPostingQuery())
    ->setViewer($this->getViewer())
    ->withPHIDs($posting_phids)
    ->execute();

    $postings = mpull($postings, null, 'getPHID');
    foreach ($applicants as $key => $apply) {
      $apply_item = idx($postings, $apply->getPostingPHID());
      if (!$apply_item) {
        unset($applicants[$key]);
        $this->didRejectResult($apply);
        continue;
      }
      $apply->attachJob($apply_item);
    }

    return $applicants;
  }

  public function withPostingPHIDs(array $phids) {
    $this->postingPHIDs = $phids;
    return $this;
  }

  public function withApplicantPHIDs(array $phids) {
    $this->applicantPHIDs = $phids;
    return $this;
  }

  public function withInviterPHIDs(array $phids) {
    $this->inviterPHIDs = $phids;
    return $this;
  }

  public function withStatuses(array $statuses) {
    $this->statuses = $statuses;
    return $this;
  }

  private function shouldJoinJob() {
    return (bool) $this->statuses;
  }

  protected function loadPage() {
    return $this->loadStandardPage($this->newResultObject());
  }

  public function newResultObject() {
    return new JobPostingApplicant();
  }

  public function getTableName() {
    return sev2table('job_postingapplicant');
  }

  protected function getPrimaryTableAlias() {
    return 'job_postingapplicant';
  }

  protected function buildWhereClauseParts(AphrontDatabaseConnection $conn) {
    $where = parent::buildWhereClauseParts($conn);

    if ($this->postingPHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'job_postingapplicant.postingPHID IN (%Ls)',
        $this->postingPHIDs);
    }

    if (count($this->applicantPHIDs) > 0) {
      $where[] = qsprintf(
        $conn,
        'job_postingapplicant.applicantPHID IN (%Ls)',
        $this->applicantPHIDs);
    }

    if ($this->inviterPHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'job_postingapplicant.inviterPHID in (%Ls)',
        $this->inviterPHIDs);
    }

    if ($this->statuses !== null) {
      $where[] = qsprintf(
        $conn,
        'job_postingapplicant.status in (%Ls)',
        $this->statuses);
    }

    return $where;
  }

  protected function buildJoinClause(AphrontDatabaseConnection $conn) {
    $join = parent::buildJoinClause($conn);
    $post = new JobPostingQuery();

    if ($this->shouldJoinJob()) {
      $join[] = qsprintf(
        $conn,
        'JOIN %T job_posting ON job_postingapplicant.postingPHID = job_posting.phid',
        $post->getTableName());
    }

    return $join;
  }

  public function getQueryApplicationClass() {
   return 'PhabricatorJobApplication';
  }
}
