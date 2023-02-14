<?php

final class ConduitLobbyRoomHQAPIMethod extends
  ConduitLobbyAPIMethod {

    public function getAPIMethodName() {
    return 'lobby.room.hq';
  }

  public function getMethodDescription() {
    return pht('Get HQ room');
  }

  public function getMethodSummary() {
    return pht('Get HQ Room.');
  }

  protected function defineParamTypes() {
    return array();
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $user = $request->getUser();
    $is_suite = $user->getIsSuite();

    if (!$is_suite) {
      $pilot_hq = id(new ConpherenceThreadQuery())
                    ->setViewer($user)
                    ->withPilotHQ(true)
                    ->needProfileImage(true)
                    ->execute();
      if (!$pilot_hq) {
        return array();
      }

      $hq = head($pilot_hq);
    } else {
      $pilot_hq = id(new ConpherenceThreadQuery())
                    ->setViewer($user)
                    ->needProfileImage(true)
                    ->withPHIDs(array(
                      'PHID-CONP-opiuef3vuzyfugllibtc' // harcoded suite phid
                    ))
                    ->execute();
      if (!$pilot_hq) {
        return array();
      }
      $hq = head($pilot_hq);
    }

    $all_in_channels = id(new LobbyStateQuery())
                        ->setViewer($user)
                        ->needOwner(true)
                        ->withStatus(LobbyState::STATUS_IN_CHANNEL)
                        ->withIsWorking(true)
                        ->withCurrentChannel($hq->getPHID())
                        ->execute();

    $participant = array();
    $participants = array();
    $elements = array();
    $id = $hq->getID();

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

    $participating = $hq->getParticipantIfExists($user->getPHID());
    $could_join = !empty($participating);

    $elements[$id]['conpherenceID'] = $hq->getID();
    $elements[$id]['conpherencePHID'] = $hq->getPHID();
    $elements[$id]['conpherenceTitle'] = $hq->getTitle();
    $elements[$id]['messageCount'] = $hq->getMessageCount();
    $elements[$id]['conpherenceURI'] = $this->getConpherenceURI(
      $hq);
      $elements[$id]['channelTopic'] = $hq->getTopic();
    $elements[$id]['members'] = $participants;
    $elements[$id]['memberCount'] =
      count($elements[$id]['members'])
      .'/'.
      count($hq->getParticipants());
    $elements[$id]['isOwner'] = $could_join;
    $elements[$id]['isFavorite'] = false;
    $elements[$id]['isHq'] = true;

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
