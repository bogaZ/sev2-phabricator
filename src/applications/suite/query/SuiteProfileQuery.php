<?php

final class SuiteProfileQuery
  extends PhabricatorCursorPagedPolicyAwareQuery {

  private $ids;
  private $phids;
  private $ownerPHIDs;

  public static function loadProfileForUser(
    PhabricatorUser $user,
    PhabricatorContentSource $content_source) {

    $profile = id(new self())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withOwnerPHIDs(array($user->getPHID()))
      ->executeOne();

    if (!$profile) {
      // Only loads for suite user
      if (!$user->getIsSuite()) {
        throw new SuiteInvalidUserFlagException(
          'User not registered via Suite');
      }

      $profile = SuiteProfile::createNewProfile($user, $content_source);
    }

    return $profile;
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

  public function newResultObject() {
    return new SuiteProfile();
  }

  protected function loadPage() {
    return $this->loadStandardPage($this->newResultObject());
  }

  protected function getPrimaryTableAlias() {
    return 'suite_profile';
  }

  protected function buildWhereClauseParts(AphrontDatabaseConnection $conn) {
    $where = parent::buildWhereClauseParts($conn);

    if ($this->ids !== null) {
      $where[] = qsprintf(
        $conn,
        'suite_profile.id IN (%Ld)',
        $this->ids);
    }

    if ($this->phids !== null) {
      $where[] = qsprintf(
        $conn,
        'suite_profile.phid IN (%Ls)',
        $this->phids);
    }

    if ($this->ownerPHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'suite_profile.ownerPHID IN (%Ls)',
        $this->ownerPHIDs);
    }

    return $where;
  }

  protected function willLoadPage(array $page) {

    $user_phids = mpull($page, 'getOwnerPHID');

    $users = id(new PhabricatorPeopleQuery())
      ->setViewer($this->getViewer())
      ->setParentQuery($this)
      ->withPHIDs($user_phids)
      ->execute();
    $users = mpull($users, null, 'getPHID');

    foreach ($page as $key => $profile) {
      $user = idx($users, $profile->getOwnerPHID());

      if (!$user) {
        unset($page[$key]);
        $this->didRejectResult($profile);
        continue;
      }

      $profile->attachOwner($user);
    }

    return $page;
  }

  public function getQueryApplicationClass() {
    return 'PhabricatorSuiteApplication';
  }

}
