<?php

final class SuiteBalanceQuery
  extends PhabricatorCursorPagedPolicyAwareQuery {

  private $ids;
  private $phids;
  private $ownerPHIDs;
  private $accountPHIDs;

  public static function loadBalanceForUserAccount(
    PhabricatorUser $user,
    PhortuneAccount $account,
    PhabricatorContentSource $content_source) {

    $balance = id(new self())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withOwnerPHIDs(array($user->getPHID()))
      ->executeOne();

    if (!$balance) {
      $balance = SuiteBalance::createNewBalance($user,
        $account, $content_source);
    }

    return $balance;
  }

  public function withIDs(array $ids) {
    $this->ids = $ids;
    return $this;
  }

  public function withPHIDs(array $phids) {
    $this->phids = $phids;
    return $this;
  }

  public function withOwnerPHIDs(array $owner_phids) {
    $this->ownerPHIDs = $owner_phids;
    return $this;
  }

  public function withAccountPHIDs(array $acc_phids) {
    $this->accountPHIDs = $acc_phids;
    return $this;
  }

  public function newResultObject() {
    return new SuiteBalance();
  }

  protected function loadPage() {
    return $this->loadStandardPage($this->newResultObject());
  }

  protected function getPrimaryTableAlias() {
    return 'suite_balance';
  }

  protected function buildWhereClauseParts(AphrontDatabaseConnection $conn) {
    $where = parent::buildWhereClauseParts($conn);

    if ($this->ids !== null) {
      $where[] = qsprintf(
        $conn,
        'suite_balance.id IN (%Ld)',
        $this->ids);
    }

    if ($this->phids !== null) {
      $where[] = qsprintf(
        $conn,
        'suite_balance.phid IN (%Ls)',
        $this->phids);
    }

    if ($this->ownerPHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'suite_balance.ownerPHID IN (%Ls)',
        $this->ownerPHIDs);
    }

    if ($this->accountPHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'suite_balance.accountPHID IN (%Ls)',
        $this->accountPHIDs);
    }

    return $where;
  }

  public function getQueryApplicationClass() {
    return 'PhabricatorSuiteApplication';
  }

}
