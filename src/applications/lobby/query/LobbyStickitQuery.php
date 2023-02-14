<?php

final class LobbyStickitQuery
  extends PhabricatorCursorPagedPolicyAwareQuery {

  private $ids;
  private $phids;
  private $ownerPHIDs;
  private $noteType;
  private $exNoteType;
  private $withProjectGoalsPHIDs;
  private $isArchived;
  private $dateCreatedAfter;
  private $dateCreatedBefore;


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

  public function withNoteType($note_type) {
    $this->noteType = $note_type;
    return $this;
  }

  public function isArchived($is_archived) {
    $this->isArchived = $is_archived;
    return $this;
  }

  public function withoutNoteType($ex_note_type) {
    $this->exNoteType = $ex_note_type;
    return $this;
  }

  public function withProjectGoalsPHIDs($with_project) {
    $this->withProjectGoalsPHIDs = $with_project;
    return $this;
  }

  public function withDateCreatedAfter($date_created_after) {
    $this->dateCreatedAfter = $date_created_after;
    return $this;
  }

  public function withDateCreatedBefore($date_created_before) {
    $this->dateCreatedBefore = $date_created_before;
    return $this;
  }


  public function withOwnerPHIDs(array $owner_phids) {
    $this->ownerPHIDs = $owner_phids;
    return $this;
  }

  public function newResultObject() {
    return new LobbyStickit();
  }

  protected function loadPage() {
    return $this->loadStandardPage($this->newResultObject());
  }

  protected function buildWhereClauseParts(AphrontDatabaseConnection $conn) {
    $where = parent::buildWhereClauseParts($conn);

    if ($this->ids !== null) {
      $where[] = qsprintf(
        $conn,
        '%T.id IN (%Ld)',
        sev2table('lobby_stickit'),
        $this->ids);
    }
    if ($this->isArchived !== null) {
      $where[] = qsprintf(
        $conn,
        '%T.isArchived = %d',
        sev2table('lobby_stickit'),
        $this->isArchived);
    }

    if ($this->phids !== null) {
      $phids = $this->phids;
      if (!empty($phids)) {
        $where[] = qsprintf(
          $conn,
          '%T.phid IN (%Ls)',
          sev2table('lobby_stickit'),
          $this->phids);
      }
    }

    if ($this->ownerPHIDs !== null) {
      $where[] = qsprintf(
        $conn,
        '%T.ownerPHID IN (%Ls)',
        sev2table('lobby_stickit'),
        $this->ownerPHIDs);
    }

    if ($this->noteType !== null) {
      $where[] = qsprintf(
        $conn,
        '%T.noteType = %s',
        sev2table('lobby_stickit'),
        $this->noteType);
    }

    if ($this->exNoteType !== null) {
      $where[] = qsprintf(
        $conn,
        '%T.noteType != %s',
        sev2table('lobby_stickit'),
        $this->exNoteType);
    }

    if ($this->dateCreatedAfter) {
      $where[] = qsprintf(
        $conn,
        '%T.dateCreated >= %d',
        sev2table('lobby_stickit'),
        $this->dateCreatedAfter);
    }

    if ($this->dateCreatedBefore) {
      $where[] = qsprintf(
        $conn,
        '%T.dateCreated <= %d',
        sev2table('lobby_stickit'),
        $this->dateCreatedBefore);
    }

    if ($this->withProjectGoalsPHIDs !== null) {
      $phids = array();
      $tags = id(new ConpherenceThreadQuery())
              ->setViewer(PhabricatorUser::getOmnipotentUser())
              ->withTagsPHIDs($this->withProjectGoalsPHIDs)
              ->execute();

      foreach ($tags as $key => $value) {
          $stickit_phids = PhabricatorEdgeQuery::loadDestinationPHIDs(
            $key,
            ConpherenceThreadHasGoalsEdgeType::EDGECONST);
          $phids = array_merge($phids, $stickit_phids);
      }
      if (empty($phids)) {
        //  Create undefined phid so when user try to search object it'll
        //  return null
        $phids = ['PHID-LBYS-NONE'];
      }
      $where[] = qsprintf(
        $conn,
        '%T.phid IN (%Ls)',
        sev2table('lobby_stickit'),
        $phids);
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

    return $objects;
  }

  public function getQueryApplicationClass() {
    return 'PhabricatorLobbyApplication';
  }

}
