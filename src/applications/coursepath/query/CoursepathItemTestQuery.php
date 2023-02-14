<?php

final class CoursepathItemTestQuery
  extends CoursepathQuery {

  private $ids;
  private $phids;
  private $title;
  private $itemPHIDs = array();
  private $types;
  private $severities;
  private $statuses;
  private $testCodes;
  private $autoGrade;
  private $stacks;

  private $viewer;
  private $startDate;
  private $endDate;
  private $submitterPHIDs = array();

  private $needOptions;
  private $needSubmissions;

  public function withIDs(array $ids) {
    $this->ids = $ids;
    return $this;
  }

  public function withPHIDs(array $phids) {
    $this->phids = $phids;
    return $this;
  }

  public function withItemPHIDs(array $phids) {
    $this->itemPHIDs = $phids;
    return $this;
  }

  public function withTypes(array $types) {
    $this->types = $types;
    return $this;
  }

  public function withTitles($title) {
    $this->title = $title;
    return $this;
  }

  public function withSeverities(array $severities) {
    $this->severities = $severities;
    return $this;
  }

  public function withTestCodes(array $test_codes) {
    $this->testCodes = $test_codes;
    return $this;
  }

  public function withStatuses(array $statuses) {
    $this->statuses = $statuses;
    return $this;
  }

  public function withStacks(array $stacks) {
    $this->stacks = $stacks;
    return $this;
  }

  public function withAutoGrade($auto_grade) {
    $this->autoGrade = $auto_grade;
    return $this;
  }

  public function withViewer($viewer) {
    $this->viewer = $viewer;
    return $this;
  }

  public function withSubmitterPHIDs(array $submitter_phids) {
    $this->submitterPHIDs = $submitter_phids;
    return $this;
  }

  public function withStartDate($date) {
    $this->startDate = $date;
    return $this;
  }

  public function withEndDate($date) {
    $this->endDate = $date;
    return $this;
  }

  public function needOptions($need_options) {
    $this->needOptions = $need_options;
    return $this;
  }

  public function needSubmissions($need_submissions) {
    $this->needSubmissions = $need_submissions;
    return $this;
  }

  private function shouldJoinItem() {
    return $this->itemPHIDs && !empty($this->itemPHIDs);
  }

  public function newResultObject() {
    return new CoursepathItemTest();
  }

  protected function loadPage() {
    return $this->loadStandardPage($this->newResultObject());
  }

  protected function buildWhereClauseParts(AphrontDatabaseConnection $conn) {
    $where = parent::buildWhereClauseParts($conn);

    if ($this->ids !== null) {
      $where[] = qsprintf(
        $conn,
        'coursepath_itemtest.id IN (%Ld)',
        $this->ids);
    }

    if ($this->phids !== null) {
      $where[] = qsprintf(
        $conn,
        'coursepath_itemtest.phid IN (%Ls)',
        $this->phids);
    }

    if ($this->statuses !== null) {
      $where[] = qsprintf(
        $conn,
        'coursepath_itemtest.statuses IN (%Ls)',
        $this->statuses);
    }

    if (count($this->itemPHIDs) > 0) {
      $where[] = qsprintf(
        $conn,
        'coursepath_itemtest.itemPHID IN (%Ls)',
        $this->itemPHIDs);
    }

    if ($this->types) {
      $where[] = qsprintf(
        $conn,
        'coursepath_itemtest.type IN (%Ls)',
        $this->types);
    }

    if ($this->severities) {
      $where[] = qsprintf(
        $conn,
        'coursepath_itemtest.severity IN (%Ls)',
        $this->severities);
    }

    if ($this->testCodes) {
      $where[] = qsprintf(
        $conn,
        'coursepath_itemtest.testCode IN (%Ls)',
        $this->testCodes);
    }

    if ($this->title !== null) {
      $where[] = qsprintf(
        $conn,
        'coursepath_itemtest.title LIKE %>',
        $this->title);
    }

    if ($this->autoGrade) {
      $where[] = qsprintf(
        $conn,
        'coursepath_itemtest.isNotAutomaticallyGraded IN (%Ls)',
        $this->autoGrade);
    }

    if ($this->stacks) {
      $where[] = qsprintf(
        $conn,
        'coursepath_itemtest.stack IN (%Ls)',
        $this->stacks);
    }

    return $where;
  }

  protected function getPrimaryTableAlias() {
    return 'coursepath_itemtest';
  }

  protected function willFilterPage(array $tests) {
    assert_instances_of($tests, 'CoursepathItemTest');

    $ids = mpull($tests, 'getID');

    if ($this->needOptions) {
      $options = id(new CoursepathItemTestOption())->loadAllWhere(
        'testID IN (%Ld)',
        $ids);

      $options = mgroup($options, 'getTestID');
      foreach ($tests as $test) {
        $test->attachOptions(idx($options, $test->getID(), array()));
      }
    }

    $phids = mpull($tests, 'getPHID');
    if ($this->needSubmissions) {
      $submissions = id(new CoursepathItemTestSubmissionQuery())
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->withTestPHIDs($phids);

      if (count($this->submitterPHIDs) > 0) {
        $submissions = $submissions->withCreatorPHIDs($this->submitterPHIDs);
      }

      if ($this->startDate) {
        $submissions = $submissions->withStartDate($this->startDate);
      }

      if ($this->endDate) {
        $submissions = $submissions->withEndDate($this->endDate);
      }

      $submissions = $submissions->execute();

      $submissions = mgroup($submissions, 'getTestPHID');
      foreach ($tests as $test) {
        $test->attachSubmissions(idx($submissions, $test->getPHID(), array()));
      }
    }

    return $tests;
  }

  protected function buildJoinClauseParts(AphrontDatabaseConnection $conn) {
    $join = parent::buildJoinClauseParts($conn);
    $items = new self();


    if ($this->shouldJoinItem()) {
      $join[] = qsprintf(
        $conn,
        'JOIN %T coursepath_item ON
        coursepath_itemtest.itemPHID = coursepath_item.phid',
        id(new CoursepathItem())->getTableName());
    }

    return $join;
  }

}
