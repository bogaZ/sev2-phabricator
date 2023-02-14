<?php

final class CoursepathItemTestSubmissionQuery
  extends CoursepathQuery {

  private $ids;
  private $phids;
  private $testPHIDs = array();
  private $creatorPHIDs = array();
  private $score;
  private $startDate;
  private $endDate;
  private $session;

  private $needTest;
  private $needQuizTest;

  public function withIDs(array $ids) {
    $this->ids = $ids;
    return $this;
  }

  public function withPHIDs(array $phids) {
    $this->phids = $phids;
    return $this;
  }

  public function withTestPHIDs(array $phids) {
    $this->testPHIDs = $phids;
    return $this;
  }

  public function withCreatorPHIDs(array $phids) {
    $this->creatorPHIDs = $phids;
    return $this;
  }

  public function withScore($score) {
    $this->score = $score;
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

  public function withSession($session) {
    $this->session = $session;
    return $this;
  }

  public function withNameNgrams($ngrams) {
    return $this->withNgramsConstraint(
      id(new CoursepathItemTestSubmissionNameNgrams()),
      $ngrams);
  }

  public function needQuizTest($need_quiz_test) {
    $this->needQuizTest = $need_quiz_test;
    return $this;
  }

  public function needTest($need_test) {
    $this->needTest = $need_test;
    return $this;
  }

  private function shouldJoinItem() {
    return $this->testPHIDs && !empty($this->testPHIDs);
  }

  public function newResultObject() {
    return new CoursepathItemTestSubmission();
  }

  protected function loadPage() {
    return $this->loadStandardPage($this->newResultObject());
  }

  protected function buildWhereClauseParts(AphrontDatabaseConnection $conn) {
    $where = parent::buildWhereClauseParts($conn);

    if ($this->ids !== null) {
      $where[] = qsprintf(
        $conn,
        'coursepath_itemtestsubmission.id IN (%Ld)',
        $this->ids);
    }

    if ($this->phids !== null) {
      $where[] = qsprintf(
        $conn,
        'coursepath_itemtestsubmission.phid IN (%Ls)',
        $this->phids);
    }

    if (count($this->creatorPHIDs) > 0) {
      $where[] = qsprintf(
        $conn,
        'coursepath_itemtestsubmission.creatorPHID IN (%Ls)',
        $this->creatorPHIDs);
    }

    if (count($this->testPHIDs) > 0) {
      $where[] = qsprintf(
        $conn,
        'coursepath_itemtestsubmission.testPHID IN (%Ls)',
        $this->testPHIDs);
    }

    if ($this->score) {
      $where[] = qsprintf(
        $conn,
        'coursepath_itemtestsubmission.score = %d',
        $this->score);
    }

    if ($this->startDate) {
      $where[] = qsprintf(
        $conn,
        'coursepath_itemtestsubmission.dateModified >= %d',
        $this->startDate);
    }

    if ($this->endDate) {
      $where[] = qsprintf(
        $conn,
        'coursepath_itemtestsubmission.dateModified <= %d',
        $this->endDate);
    }

    if ($this->session) {
      $where[] = qsprintf(
        $conn,
        'coursepath_itemtestsubmission.session = %d',
        $this->session);
    }

    return $where;
  }

  protected function getPrimaryTableAlias() {
    return 'coursepath_itemtestsubmission';
  }

  protected function willFilterPage(array $submissions) {
    assert_instances_of($submissions, 'CoursepathItemTestSubmission');

    $phids = mpull($submissions, 'getTestPHID');

    foreach ($submissions as $submission) {
      if ($this->needTest) {
        $test = id(new CoursepathItemTest())->loadOneWhere(
          'phid = %s',
          $submission->getTestPHID());

        if ($test) {
          $submission->attachTest($test);
        }
      }

      if ($this->needQuizTest) {
        $test = id(new CoursepathItemTest())->loadOneWhere(
          'phid = %s AND type = %s',
          $submission->getTestPHID(),
          CoursepathItemTest::TYPE_QUIZ);

        if ($test) {
          $submission->attachTest($test);
        }
      }
    }

    return $submissions;
  }

  protected function buildJoinClauseParts(AphrontDatabaseConnection $conn) {
    $join = parent::buildJoinClauseParts($conn);
    $tests = new CoursepathItemTestQuery();

    if ($this->shouldJoinItem()) {
      $join[] = qsprintf(
        $conn,
        'JOIN %T coursepath_itemtest ON
        coursepath_itemtestsubmission.testPHID = coursepath_itemtest.phid',
        id(new CoursepathItemTest())->getTableName());
    }

    return $join;
  }

}
