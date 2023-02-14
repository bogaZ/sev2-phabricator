<?php

final class ConduitLobbyRoomListAPIMethod extends
  ConduitLobbyAPIMethod {

    public function getAPIMethodName() {
    return 'lobby.room.list';
  }

  public function getMethodDescription() {
    return pht('Lobby Room List');
  }

  public function getMethodSummary() {
    return pht('Lobby Room List.');
  }

  protected function defineParamTypes() {
    return array(
      'isDeleted' => 'optional boolean',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $user = $request->getUser();

    if ($user->getIsSuite()) {
      return $this->suiteRoomList($request);
    }

    return $this->roomList($request);
  }

  protected function roomList(ConduitAPIRequest $request) {
    $user = $request->getUser();
    $participants = array();
    $all_in_channels = id(new LobbyStateQuery())
                        ->setViewer($user)
                        ->needOwner(true)
                        ->withStatus(LobbyState::STATUS_IN_CHANNEL)
                        ->withIsWorking(true)
                        ->execute();
    $participant = array();
    $elements = array();
    $is_deleted = $request->getValue('isDeleted');

    $query = id(new ConpherenceThreadQuery())
        ->needProfileImage(true)
        ->setViewer($user);

    if ($is_deleted !== null) {
      $query->withIsDeleted((bool)$is_deleted);
    }

    $conpherences = $query->execute();

    $transactions = id(new ConpherenceTransactionQuery())
      ->setViewer($user)
      ->withTransactionTypes(
        array(PhabricatorCoreCreateTransaction::TRANSACTIONTYPE))
      ->execute();

    foreach ($conpherences as $conpherence) {
      $id = $conpherence->getID();

      if ($conpherence->getIsHQ()) {
        continue;
      }

      $all_in_channels = id(new LobbyStateQuery())
          ->setViewer($user)
          ->needOwner(true)
          ->withStatus(LobbyState::STATUS_IN_CHANNEL)
          ->withCurrentChannel(
            $conpherence->getPHID())
          ->withIsWorking(true)
          ->execute();

      foreach ($all_in_channels as $channel) {
        $owner = $channel->getOwner();

        $participant['userPHID'] = $owner->getPHID();
        $participant['username'] = $owner->getUsername();
        $participant['fullname'] = $owner->getFullName();
        $participant['currentTask'] = $channel->getCurrentTask();
        $participant['status'] = $this->getStatus($channel->getStatus());
        $participant['profileImageURI'] = $owner->getProfileImageURI();
        $participant['device'] = $channel->getDevice();

        $participants[] = $participant;
      }

      $is_joinable = false;

      if (count($conpherence->getParticipants())) {
        $map_participant = array_map(function ($object) {
          return $object->getParticipantPHID();
        }, $conpherence->getParticipants());
        $is_joinable = in_array($user->getPHID(), $map_participant, true);
      }

      $elements[$id]['conpherenceID'] = $conpherence->getID();
      $elements[$id]['conpherencePHID'] = $conpherence->getPHID();
      $elements[$id]['conpherenceTitle'] = $conpherence->getTitle();
      $elements[$id]['messageCount'] = $conpherence->getMessageCount();
      $elements[$id]['conpherenceURI'] = $this->getConpherenceURI(
        $conpherence);
        $elements[$id]['channelTopic'] = $conpherence->getTopic();
      $elements[$id]['members'] = $participants;
      $elements[$id]['memberCount'] =
        count($elements[$id]['members'])
        .'/'.
        count($conpherence->getParticipants());
      $elements[$id]['isOwner'] = true;
      $elements[$id]['isFavorite'] = false;
      $elements[$id]['isDeleted'] = (int)$conpherence->getIsDeleted();
      $elements[$id]['isJoinable'] = $is_joinable;
      $elements[$id]['isPublic'] = $conpherence->getIsPublic();

      $participants = array();

      foreach ($transactions as $transaction) {
        if ($conpherence->getPHID() === $transaction->getObjectPHID()) {
          $elements[$id]['ownerPHID'] = $transaction->getAuthorPHID();
        }
      }

      $view_policy = $conpherence->getViewPolicy();
      if ($view_policy == 'obj.conpherence.members') {
        $view_policy = 'participants';
      }

      $edit_policy = $conpherence->getEditPolicy();
      if ($edit_policy == 'obj.conpherence.members') {
        $edit_policy = 'participants';
      }

      $elements[$id]['policy'] = array(
        'view' => $view_policy,
        'edit' => $edit_policy,
      );
    }
    return $elements;
  }

  protected function suiteRoomList(ConduitAPIRequest $request) {
    $user = $request->getUser();
    $participants = array();
    $participant = array();
    $elements = array();
    $is_deleted = $request->getValue('isDeleted');

    $query = id(new ConpherenceThreadQuery())
        ->needProfileImage(true)
        ->setViewer($user);

    if ($is_deleted !== null) {
      $query->withIsDeleted((bool)$is_deleted);
    }

    $conpherences = $query->execute();

    $transactions = id(new ConpherenceTransactionQuery())
      ->setViewer($user)
      ->withTransactionTypes(
        array(PhabricatorCoreCreateTransaction::TRANSACTIONTYPE))
      ->execute();

    foreach ($conpherences as $conpherence) {
      $id = $conpherence->getID();

      if ($conpherence->getIsHQ()) {
        continue;
      }

      $all_in_channels = id(new LobbyStateQuery())
          ->setViewer($user)
          ->needOwner(true)
          ->withStatus(LobbyState::STATUS_IN_CHANNEL)
          ->withCurrentChannel(
            $conpherence->getPHID())
          ->withIsWorking(true)
          ->execute();

      foreach ($all_in_channels as $channel) {
        $owner = $channel->getOwner();

        $participant['userPHID'] = $owner->getPHID();
        $participant['username'] = $owner->getUsername();
        $participant['fullname'] = $owner->getFullName();
        $participant['currentTask'] = $channel->getCurrentTask();
        $participant['status'] = $this->getStatus($channel->getStatus());
        $participant['profileImageURI'] = $owner->getProfileImageURI();
        $participant['device'] = $channel->getDevice();

        $participants[] = $participant;
      }

      $elements[$id]['conpherenceID'] = $conpherence->getID();
      $elements[$id]['conpherencePHID'] = $conpherence->getPHID();
      $elements[$id]['conpherenceTitle'] = $conpherence->getTitle();
      $elements[$id]['messageCount'] = $conpherence->getMessageCount();
      $elements[$id]['conpherenceURI'] = $this->getConpherenceURI(
        $conpherence);
        $elements[$id]['channelTopic'] = $conpherence->getTopic();
      $elements[$id]['members'] = $participants;
      $elements[$id]['memberCount'] =
        count($elements[$id]['members'])
        .'/'.
        count($conpherence->getParticipants());
      $elements[$id]['isOwner'] = true;
      $elements[$id]['isFavorite'] = false;
      $elements[$id]['isDeleted'] = (int)$conpherence->getIsDeleted();

      $participants = array();

      foreach ($transactions as $transaction) {
        if ($conpherence->getPHID() === $transaction->getObjectPHID()) {
          $elements[$id]['ownerPHID'] = $transaction->getAuthorPHID();
        }
      }

      $view_policy = $conpherence->getViewPolicy();
      if ($view_policy == 'obj.conpherence.members') {
        $view_policy = 'participants';
      }

      $edit_policy = $conpherence->getEditPolicy();
      if ($edit_policy == 'obj.conpherence.members') {
        $edit_policy = 'participants';
      }

      $elements[$id]['policy'] = array(
        'view' => $view_policy,
        'edit' => $edit_policy,
      );

      $elements[$id]['isJoinable'] = PhabricatorPolicyFilter::hasCapability(
        $user,
        $conpherence,
        PhabricatorPolicyCapability::CAN_VIEW);
    }

    return $elements;
  }

  private function getConpherenceURI(ConpherenceThread $conpherence) {
    $id = $conpherence->getID();
    return PhabricatorEnv::getProductionURI(
      $this->getApplication()->getApplicationURI($id));
  }

  private function getStatus(int $status_id) {
    $statuses = LobbyState::getStatusMap();

    if (!isset($statuses[$status_id])) {
      return 'Just Mingling';
    }

    return $statuses[$status_id];
  }

}
