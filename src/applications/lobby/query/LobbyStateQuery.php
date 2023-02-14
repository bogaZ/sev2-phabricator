<?php

final class LobbyStateQuery
  extends PhabricatorCursorPagedPolicyAwareQuery {

  private $ids;
  private $phids;
  private $prefix;
  private $ownerPHIDs;
  private $excludedOwnerPHIDs;
  private $currentChannel;
  private $isWorking;
  private $status;

  private $onlyStatuses;
  private $excludedStatuses;

  private $dateModifiedSince;
  private $dateModifiedBefore;

  private $needOwner;

  public function needOwner($need_owner) {
    $this->needOwner = $need_owner;
    return $this;
  }

  public function withIDs(array $ids) {
    $this->ids = $ids;
    return $this;
  }

  public function withPHIDs(array $phids) {
    $this->phids = $phids;
    return $this;
  }

  public function withIsWorking($active) {
    $this->isWorking = $active;
    return $this;
  }

  public function withCurrentChannel($channel) {
    $this->currentChannel = $channel;
    return $this;
  }

  public function withStatus($status) {
    $this->status = $status;
    return $this;
  }

  public function withPrefix($prefix) {
    $this->prefix = $prefix;
    return $this;
  }

  public function withStatusOnly($statuses) {
    $this->onlyStatuses = $statuses;
    return $this;
  }

  public function withStatusExcluded($statuses) {
    $this->excludedStatuses = $statuses;
    return $this;
  }

  public function withOwnerPHIDs(array $owner_phids) {
    $this->ownerPHIDs = $owner_phids;
    return $this;
  }

  public function withoutOwnerPHIDs(array $owner_phids) {
    $this->excludedOwnerPHIDs = $owner_phids;
    return $this;
  }

  public function withDateModifiedSince($timestamp) {
    $this->dateModifiedSince = $timestamp;
    return $this;
  }

  public function withDateModifiedBefore($timestamp) {
    $this->dateModifiedBefore = $timestamp;
    return $this;
  }

  public function newResultObject() {
    return new LobbyState();
  }

  protected function loadPage() {
    return $this->loadStandardPage($this->newResultObject());
  }

  protected function getPrimaryTableAlias() {
    return 'state';
  }

  protected function hasColumnSpec() {
    return true;
  }

  protected function getColumns() {
    $column_names = array_keys(id(new LobbyState())->getSchemaColumns());

    $t = $this->getPrimaryTableAlias();
    foreach ($column_names as $i => $column_name) {
      $column_names[$i] = pht('%s.%s', $t, $column_name);
    }

    return $column_names;
  }

  protected function newPagingMapFromPartialObject($object) {
    return array(
      'id' => (int)$object->getID(),
      'dateModified' => (int)$object->getDateModified(),
    );
  }

  protected function buildWhereClauseParts(AphrontDatabaseConnection $conn) {
    $where = parent::buildWhereClauseParts($conn);

    if ($this->ids !== null) {
      $where[] = qsprintf(
        $conn,
        'state.id IN (%Ld)',
        $this->ids);
    }

    if ($this->phids !== null) {
      $where[] = qsprintf(
        $conn,
        'state.phid IN (%Ls)',
        $this->phids);
    }

    if ($this->prefix !== null) {
      $where[] = qsprintf(
        $conn,
        'state.currentTask LIKE %>',
        $this->prefix);
    }

    if ($this->ownerPHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'state.ownerPHID IN (%Ls)',
        $this->ownerPHIDs);
    }

    if ($this->excludedOwnerPHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'state.ownerPHID NOT IN (%Ls)',
        $this->excludedOwnerPHIDs);
    }

    if ($this->isWorking !== null) {
      $where[] = qsprintf(
        $conn,
        'state.isWorking = %d',
        (int)$this->isWorking);
    }

    if ($this->status !== null) {
      $where[] = qsprintf(
        $conn,
        'state.status = %d',
        (int)$this->status);
    }

    if ($this->onlyStatuses !== null) {
      $where[] = qsprintf(
        $conn,
        'state.status IN (%Ld)',
        $this->onlyStatuses);
    }

    if ($this->excludedStatuses !== null) {
      $where[] = qsprintf(
        $conn,
        'state.status NOT IN (%Ld)',
        $this->excludedStatuses);
    }

    if ($this->currentChannel !== null) {
      $where[] = qsprintf(
        $conn,
        'state.currentChannel = %s',
        $this->currentChannel);
    }

    if ($this->dateModifiedSince !== null) {
      $where[] = qsprintf(
        $conn,
        'state.dateModified > %d',
        $this->dateModifiedSince);
    }

    if ($this->dateModifiedBefore !== null) {
      $where[] = qsprintf(
        $conn,
        'state.dateModified < %d',
        $this->dateModifiedBefore);
    }

    $where[] = qsprintf(
        $conn,
        'user.isDisabled < %d',
        1);

    return $where;
  }

  protected function willFilterPage(array $items) {
    assert_instances_of($items, 'LobbyState');

    $phids = mpull($items, 'getOwnerPHID');

    if ($this->needOwner) {
      $owners = id(new PhabricatorPeopleQuery())
                      ->setViewer(PhabricatorUser::getOmnipotentUser())
                      ->withPHIDs($phids)
                      ->withIsDisabled(false)
                      ->needProfile(true)
                      ->needProfileImage(true)
                      ->execute();

      $owners = mgroup($owners, 'getPHID');
      foreach ($items as $item) {
        $matchedOwners = idx($owners, $item->getOwnerPHID());
        $matchedOwner = head($matchedOwners);

        if ($matchedOwner && $matchedOwner instanceof PhabricatorUser) {
          $matchedOwner->loadUserProfile();
          $item->attachOwner($matchedOwner);
        }
      }
    }

    return $items;
  }

  protected function getDefaultOrderVectjoin_databaseor() {
    return array('dateModified', 'id');
  }

  public function getBuiltinOrders() {
    return array(
      'start' => array(
        'vector' => array('dateModified', 'id'),
        'name' => 'Updated (Latest first)',
      ),
    ) + parent::getBuiltinOrders();
  }

  public function getOrderableColumns() {
    return parent::getOrderableColumns() + array(
      'dateModified' => array(
        'table' => $this->getPrimaryTableAlias(),
        'column' => 'dateModified',
        'type' => 'int',
        'unique' => false,
        'reverse' => false,
      ),
      'username' => array(
        'table' => 'user',
        'column' => 'username',
        'type' => 'string',
        'reverse' => true,
        'unique' => true,
      ),
    );
  }

  protected function buildJoinClauseParts(AphrontDatabaseConnection $conn) {
    $join = parent::buildJoinClauseParts($conn);
    $people = new PhabricatorUser();
    $storage_namespace = PhabricatorLiskDAO::getDefaultStorageNamespace();
    $join_database = $storage_namespace.'_user';

    $query = pht(
      'JOIN %s.%s user ON state.ownerPHID = user.phid',
      $join_database,
      $people->getTableName());

    $join[] = qsprintf(
      $conn,
      $query);

    return $join;
  }

  protected function shouldJoinPeople() {
    return !empty($this->ownerPHIDs) || $this->needOwner;
  }

  public function getQueryApplicationClass() {
    return 'PhabricatorLobbyApplication';
  }

}
