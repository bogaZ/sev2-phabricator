<?php

final class LobbyModeratorQuery
  extends PhabricatorCursorPagedPolicyAwareQuery {

  private $ids;
  private $phids;
  private $ownerPHIDs;
  private $channelPHIDs;
  private $moderatorPHIDs;

  private $needOwner;
  private $needChannel;
  private $needModerator;

  public function needOwner($need_owner) {
    $this->needOwner = $need_owner;
    return $this;
  }

  public function needChannel($need_channel) {
    $this->needChannel = $need_channel;
    return $this;
  }

  public function needModerator($need_moderator) {
    $this->needModerator = $need_moderator;
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

  public function withOwnerPHIDs(array $owner_phids) {
    $this->ownerPHIDs = $owner_phids;
    return $this;
  }

  public function withChannelPHIDs(array $channel_phids) {
    $this->channelPHIDs = $channel_phids;
    return $this;
  }

  public function withModeratorPHIDs(array $moderator_phids) {
    $this->moderatorPHIDs = $moderator_phids;
    return $this;
  }

  public function newResultObject() {
    return new LobbyModerator();
  }

  protected function loadPage() {
    return $this->loadStandardPage($this->newResultObject());
  }

  protected function getPrimaryTableAlias() {
    return 'moderators';
  }

  protected function buildWhereClauseParts(AphrontDatabaseConnection $conn) {
    $where = parent::buildWhereClauseParts($conn);

    if ($this->ids !== null) {
      $where[] = qsprintf(
        $conn,
        'moderators.id IN (%Ld)',
        $this->ids);
    }

    if ($this->phids !== null) {
      $where[] = qsprintf(
        $conn,
        'moderators.phid IN (%Ls)',
        $this->phids);
    }

    if ($this->ownerPHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'moderators.ownerPHID IN (%Ls)',
        $this->ownerPHIDs);
    }

    if ($this->channelPHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'moderators.channelPHID IN (%Ls)',
        $this->channelPHIDs);
    }

    if ($this->moderatorPHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        'moderators.moderatorPHID IN (%Ls)',
        $this->moderatorPHIDs);
    }

    return $where;
  }

  protected function didFilterPage(array $objects) {
    if ($this->needOwner) {
      foreach ($objects as $object) {
        $owner = $object->loadUser();
        $object->attachOwner($owner);
      }
    }

    if ($this->needChannel) {
      foreach ($objects as $object) {
        $channel = $object->loadChannel();
        $object->attachChannel($channel);
      }
    }

    if ($this->needModerator) {
      foreach ($objects as $object) {
        $moderator = $object->loadModerator();
        $object->attachModerator($moderator);
      }
    }

    return $objects;
  }

  public function getQueryApplicationClass() {
    return 'PhabricatorLobbyApplication';
  }

}
