<?php

final class JobPostingQuery
  extends JobQuery {

  private $ids;
  private $phids;
  private $name;
  private $salaryFrom;
  private $salaryTo;
  private $location;
  private $rangeBegin;
  private $rangeEnd;
  private $inviteePHIDs;
  private $hostPHIDs;
  private $isLead;
  private $isCancelled;
  private $isStub;
  private $utcInitialEpochMin;
  private $utcInitialEpochMax;

  private $needRSVPs;
  private $needTechStack;
  private $needActor;
  private $needApplicants;

  public function withIDs(array $ids) {
    $this->ids = $ids;
    return $this;
  }

  public function withPHIDs(array $phids) {
    $this->phids = $phids;
    return $this;
  }

  public function withDateRange($begin, $end) {
    $this->rangeBegin = $begin;
    $this->rangeEnd = $end;
    return $this;
  }

  public function withUTCInitialEpochBetween($min, $max) {
    $this->utcInitialEpochMin = $min;
    $this->utcInitialEpochMax = $max;
    return $this;
  }

  public function withInvitedPHIDs(array $phids) {
    $this->inviteePHIDs = $phids;
    return $this;
  }

  public function withHostPHIDs(array $phids) {
    $this->hostPHIDs = $phids;
    return $this;
  }

  public function withIsCancelled($is_cancelled) {
    $this->isCancelled = $is_cancelled;
    return $this;
  }

  public function withIsLead($is_lead) {
    $this->isLead = $is_lead;
    return $this;
  }

  public function withName($name) {
    $this->name = $name;
    return $this;
  }

  public function withLocation($location) {
    $this->location = $location;
  }

  public function withSalaryFrom($salar_from) {
    $this->salaryFrom = $salar_from;
    return $this;
  }

  public function withSalaryEnd($salary_to) {
    $this->salaryTo = $salary_to;
    return $this;
  }

  public function newResultObject() {
    return new JobPosting();
  }

  public function needRSVPs(array $phids) {
    $this->needRSVPs = $phids;
    return $this;
  }

  public function needTechStack($need_tech_stack) {
    $this->needTechStack = $need_tech_stack;
    return $this;
  }

  public function needApplicants($need_applicants) {
    $this->needApplicants = $need_applicants;
    return $this;
  }

  public function needActor(PhabricatorUser $actor) {
    $this->needActor = $actor;
    return $this;
  }

  protected function getDefaultOrderVector() {
    return array('start', 'id');
  }

  public function getBuiltinOrders() {
    return array(
      'start' => array(
        'vector' => array('start', 'id'),
        'name' => pht('Event Start'),
      ),
      'modified' => array(
        'vector' => array('modified', 'id'),
        'name' => pht('Event Modified'),
      ),
    ) + parent::getBuiltinOrders();
  }

  public function getOrderableColumns() {
    return array(
      'start' => array(
        'table' => $this->getPrimaryTableAlias(),
        'column' => 'utcInitialEpoch',
        'reverse' => true,
        'type' => 'int',
        'unique' => false,
      ),
      'modified' => array(
        'table' => $this->getPrimaryTableAlias(),
        'column' => 'dateModified',
        'reverse' => false,
        'type' => 'int',
        'unique' => false,
      ),
    ) + parent::getOrderableColumns();
  }

  protected function newPagingMapFromPartialObject($object) {
    return array(
      'id' => (int)$object->getID(),
      'start' => (int)$object->getStartDateTimeEpoch(),
    );
  }

  protected function shouldLimitResults() {
    return true;
  }

  protected function loadPage() {
    $jobs = $this->loadStandardPage($this->newResultObject());

    $viewer = $this->getViewer();
    foreach ($jobs as $job) {
      $job->applyViewerTimezone($viewer);
    }

    return $jobs;
  }

  protected function willFilterPage(array $jobs) {

    $ids = mpull($jobs, 'getPHID');

    foreach ($jobs as $job) {
      if ($this->needTechStack) {
        $tech_stack = id(new JobTechStackQuery())
              ->setViewer($this->getViewer())
              ->withPostingPHIDs(array($job->getPHID()))
              ->executeOne();
        if ($tech_stack) {
          $job->attachTechStack($tech_stack);
        }
      }

      if ($this->needActor) {
        $job->setActor($this->needActor);
      }

    }

    if ($this->needApplicants) {
      $applicants = id(new JobPostingApplicant())->loadAllWhere(
        'postingPHID IN (%Ls)',
        $ids);

      $applicants = mgroup($applicants, 'getPostingPHID');
      foreach ($jobs as $job) {
        $job->attachApplicants(idx($applicants, $job->getPHID(),
        array()));
      }
    }

    return $jobs;
  }

  protected function buildWhereClauseParts(AphrontDatabaseConnection $conn) {
    $where = parent::buildWhereClauseParts($conn);

    if ($this->ids !== null) {
      $where[] = qsprintf(
        $conn,
        'job_posting.id IN (%Ld)',
        $this->ids);
    }

    if ($this->phids !== null) {
      $where[] = qsprintf(
        $conn,
        'job_posting.phid IN (%Ls)',
        $this->phids);
    }

    if ($this->name !== null) {
      $where[] = qsprintf(
        $conn,
        'job_posting.name LIKE %>',
        $this->name);
    }

    if ($this->salaryFrom !== null) {
      $where[] = qsprintf(
        $conn,
        'job_posting.salaryFrom >= %d',
        $this->salaryFrom);
    }

    if ($this->salaryTo !== null) {
      $where[] = qsprintf(
        $conn,
        'job_posting.salaryTo <= %d',
        $this->salaryTo);
    }

    if ($this->isLead !== null) {
      $where[] = qsprintf(
        $conn,
        'job_posting.isLead = %d',
        (int) $this->isLead);
    }

    if ($this->location !== null) {
      $where[] = qsprintf(
        $conn,
        'job_posting.location LIKE %>',
        $this->location);
    }

    return $where;
  }

  public function getTableName() {
    return sev2table('job_posting');
  }

  protected function getPrimaryTableAlias() {
    return 'job_posting';
  }

}
