<?php

final class Lobby {
  const TOPIC = 'lobby';

  const CHANNEL_STATE = 'state';

  const CHANNEL_STATE_JOINING = 'joining';
  const CHANNEL_STATE_LEAVING = 'leaving';

  private $viewer;

  public static function buildAvailabilityGraph(PhabricatorUser $user) {
    $ok = id(new Lobby())->setViewer($user)->allowedTojoin();

    if (!$ok) {
      return null;
    }

    return id(new LobbyAvailabilityView())
            ->setUser($user);
  }

  public static function buildInChannelBadges($states, $phid) {
    return id(new LobbyMembersView())
            ->setStates($states)
            ->setThreadPHID($phid);
  }

  public function setViewer($viewer) {
    $this->viewer = $viewer;
    return $this;
  }

  public function getViewer() {
    return $this->viewer;
  }

  /** Utility section **/
  public function joinLobby(
    PhabricatorUser $actor,
    PhabricatorContentSource $content_source,
    $device = 'phone',
    $reset_task = false) {

    if (!$this->allowedTojoin()) {
      return;
    }

    $lobby = LobbyState::getCurrent($actor, $content_source, $device);
    $previous_channel = $lobby->getCurrentChannel();
    $previous_status = $lobby->getStatus();
    $actor->loadUserProfile();

    $xactions = array();
    $xactions[] = id(new LobbyStateTransaction())
      ->setTransactionType(
        LobbyStateIsWorkingTransaction::TRANSACTIONTYPE)
      ->setNewValue(1);

    $xactions[] = id(new LobbyStateTransaction())
      ->setTransactionType(
        LobbyStateStatusTransaction::TRANSACTIONTYPE)
      ->setNewValue(LobbyState::STATUS_IN_LOBBY);

    $xactions[] = id(new LobbyStateTransaction())
      ->setTransactionType(
        LobbyStateDeviceTransaction::TRANSACTIONTYPE)
      ->setNewValue($device);

    if ($reset_task) {
      $xactions[] = id(new LobbyStateTransaction())
        ->setTransactionType(
          LobbyStateCurrentTaskTransaction::TRANSACTIONTYPE)
        ->setNewValue(null);

      $lobby->resetTaskEdge();
    }

    $editor = id(new LobbyStateEditor())
      ->setActor($actor)
      ->setContentSource($content_source)
      ->setContinueOnNoEffect(true);

    $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();

      $editor->applyTransactions($lobby, $xactions);

      $lobby->save();

    unset($unguarded);

    LobbyEvaluator::evaluate(
      array(
        'time' => time(),
        'state_phid' => $lobby->getPHID(),
        'user_phid' => $actor->getPHID(),
        'check_for_unavailable' => true,
      ));

    if ($previous_channel
      && $previous_status == LobbyState::STATUS_IN_CHANNEL) {
      // Mark leaving
      LobbyAphlict::broadcastLeavingChannel($previous_channel,
        $actor->getPHID(), array(
          'name' => $actor->getFullName(),
          'image_uri' => $actor->getProfileImageURI(),
        ));
    }

    return $lobby;
  }

  public function joinChannel(
    PhabricatorUser $actor,
    PhabricatorContentSource $content_source,
    $channel_phid) {

    if (!$this->allowedTojoin()) {
      return;
    }

    $lobby = LobbyState::getCurrent($actor, $content_source);
    $previous_channel = $lobby->getCurrentChannel();
    $actor->loadUserProfile();

    $xactions = array();
    $xactions[] = id(new LobbyStateTransaction())
      ->setTransactionType(
        LobbyStateIsWorkingTransaction::TRANSACTIONTYPE)
      ->setNewValue(1);

    $xactions[] = id(new LobbyStateTransaction())
      ->setTransactionType(
        LobbyStateStatusTransaction::TRANSACTIONTYPE)
      ->setNewValue(LobbyState::STATUS_IN_CHANNEL);

    $xactions[] = id(new LobbyStateTransaction())
      ->setTransactionType(
        LobbyStateCurrentChannelTransaction::TRANSACTIONTYPE)
      ->setNewValue($channel_phid);

    $editor = id(new LobbyStateEditor())
      ->setActor($actor)
      ->setContentSource($content_source)
      ->setContinueOnNoEffect(true);

    $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();

      $editor->applyTransactions($lobby, $xactions);

      $lobby->save();

    unset($unguarded);

    LobbyEvaluator::evaluate(
      array(
        'time' => time(),
        'state_phid' => $lobby->getPHID(),
        'user_phid' => $actor->getPHID(),
        'user_data' => array(
          'name' => $actor->getFullName(),
          'image_uri' => $actor->getProfileImageURI(),
        ),
        'check_for_unavailable' => false,
        'channel_phid' => $channel_phid,
      ));

    if ($previous_channel
      && $previous_channel != $channel_phid) {
      // Mark leaving
      LobbyAphlict::broadcastLeavingChannel($previous_channel,
        $actor->getPHID(), array(
          'name' => $actor->getFullName(),
          'image_uri' => $actor->getProfileImageURI(),
        ));
    }

    return $lobby;
  }

  public function changeStatus(
    PhabricatorUser $actor,
    PhabricatorContentSource $content_source,
    $status = 2) {

    if (!$this->allowedTojoin()) {
      return;
    }

    $lobby = LobbyState::getCurrent($actor, $content_source);
    $previous_status = $lobby->getStatus();

    $xactions = array();
    $xactions[] = id(new LobbyStateTransaction())
      ->setTransactionType(
        LobbyStateIsWorkingTransaction::TRANSACTIONTYPE)
      ->setNewValue(1);

    $xactions[] = id(new LobbyStateTransaction())
      ->setTransactionType(
        LobbyStateStatusTransaction::TRANSACTIONTYPE)
      ->setNewValue($status);

    $editor = id(new LobbyStateEditor())
      ->setActor($actor)
      ->setContentSource($content_source)
      ->setContinueOnNoEffect(true);

    $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();

      $editor->applyTransactions($lobby, $xactions);

      $lobby->save();

    unset($unguarded);

    LobbyAphlict::broadcastLobby();

    return $lobby;
  }

  public function workOnTask(
    PhabricatorUser $actor,
    PhabricatorContentSource $content_source,
    $task) {

    if (!$this->allowedTojoin()) {
      return;
    }

    $lobby = LobbyState::getCurrent($actor, $content_source);
    $previous_task = $lobby->getCurrentTask();

    if ($task == $previous_task) {
      return;
    }

    $xactions = array();
    $xactions[] = id(new LobbyStateTransaction())
      ->setTransactionType(
        LobbyStateIsWorkingTransaction::TRANSACTIONTYPE)
      ->setNewValue(1);

    $xactions[] = id(new LobbyStateTransaction())
      ->setTransactionType(
        LobbyStateCurrentTaskTransaction::TRANSACTIONTYPE)
      ->setNewValue($task);

    $editor = id(new LobbyStateEditor())
      ->setActor($actor)
      ->setContentSource($content_source)
      ->setContinueOnNoEffect(true);

    $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();

      $editor->applyTransactions($lobby, $xactions);

      $lobby->save();

    unset($unguarded);

    LobbyAphlict::broadcastLobby();

    return $lobby;
  }

  public function leavingWork(
    PhabricatorUser $actor,
    PhabricatorContentSource $content_source) {

    if (!$this->allowedTojoin()) {
      return;
    }

    $lobby = LobbyState::getCurrent($actor, $content_source);
    $lobby->resetTaskEdge();

    $xactions = array();

    $xactions[] = id(new LobbyStateTransaction())
      ->setTransactionType(
        LobbyStateIsWorkingTransaction::TRANSACTIONTYPE)
      ->setNewValue(0);
    $xactions[] = id(new LobbyStateTransaction())
      ->setTransactionType(
        LobbyStateStatusTransaction::TRANSACTIONTYPE)
      ->setNewValue(LobbyState::STATUS_IN_LOBBY);
    $xactions[] = id(new LobbyStateTransaction())
      ->setTransactionType(
        LobbyStateCurrentTaskTransaction::TRANSACTIONTYPE)
      ->setNewValue(null);

    $editor = id(new LobbyStateEditor())
      ->setActor($actor)
      ->setContentSource($content_source)
      ->setContinueOnNoEffect(true);

    $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();

      $editor->applyTransactions($lobby, $xactions);

      $lobby->save();

    unset($unguarded);

    return $lobby;
  }

  public function allowedTojoin() {
    $has_lobby = PhabricatorApplication::isClassInstalledForViewer(
      'PhabricatorLobbyApplication',
      $this->getViewer());

    if (!$has_lobby) return false;

    $app = id(new PhabricatorApplicationQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withClasses(array('PhabricatorLobbyApplication'))
      ->executeOne();

    return PhabricatorPolicyFilter::hasCapability(
          $this->getViewer(),
          $app,
          LobbyJoinCapability::CAPABILITY);
  }
}
