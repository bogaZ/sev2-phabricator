<?php

final class ConpherenceParticipantListConduitAPIMethod
  extends ConpherenceConduitAPIMethod {

  public function getAPIMethodName() {
    return 'conpherence.participant.list';
  }

  public function getMethodDescription() {
    return pht('Get Participant List Conpherence.');
  }

  protected function defineParamTypes() {
    return array(
      'conpherencePHID' =>    'required string',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $conpherence_phid = $request->getValue('conpherencePHID');
    $viewer = $request->getViewer();
    $results = array();
    $result = array();

    $conpherence_participants = id(new ConpherenceParticipantQuery())
        ->withConpherencePHIDs(array($conpherence_phid))
        ->execute();
    $participant_phids = mpull($conpherence_participants, 'getParticipantPHID');

    if (!empty($participant_phids)) {
      $users = id(new PhabricatorPeopleQuery())
          ->setViewer($viewer)
          ->needProfileImage(true)
          ->withPHIDs($participant_phids)
          ->execute();

      foreach ($users as $user) {
        $result['id'] = $user->getID();
        $result['phid'] = $user->getPHID();
        $result['username'] = $user->getUsername();
        $result['fullname'] = $user->getFullName();
        $result['realname'] = $user->getRealName();
        $result['profileImageURI'] = $user->getProfileImageURI();
        $result['jid'] = $user->getJid();

        $lobby_state = id(new LobbyStateQuery())
            ->setViewer($viewer)
            ->withOwnerPHIDs(array($user->getPHID()))
            ->executeOne();
        $result['device'] = null;
        if ($lobby_state) {
          $result['device'] = $lobby_state->getDevice();
        }

        if ($lobby_state) {
          $channel = id(new ConpherenceThreadQuery())
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->withPHIDS(array($lobby_state->getCurrentChannel()))
            ->executeOne();
          $statuses = LobbyState::getStatusMap();

          $title = 'Lobby';
          if ($channel) {
            $title = $channel->getTitle();
          }
          $result['state'] = array(
            'status' => $statuses[$lobby_state->getStatus()],
            'statusIcon' => $lobby_state->getStatusIcon(),
            'currentTask' => $lobby_state->getCurrentTask(),
            'currentChannel' => $title,
            'currentChannelPHID' => $lobby_state->getCurrentChannel(),
          );
        }

        $result['roles'] = array();

        if ($user->getIsDisabled()) {
          $result['roles'][] = 'disabled';
        }

        if ($user->getIsSystemAgent()) {
          $result['roles'][] = 'bot';
        }

        if ($user->getIsMailingList()) {
          $result['roles'][] = 'list';
        }

        if ($user->getIsAdmin()) {
          $result['roles'][] = 'admin';
        }

        if ($user->getIsEmailVerified()) {
          $result['roles'][] = 'verified';
        }

        if ($user->getIsApproved()) {
          $result['roles'][] = 'approved';
        }

        if ($user->isUserActivated()) {
          $result['roles'][] = 'activated';
        }

        if ($user->getIsForDev()) {
          $result['roles'][] = 'dev';
        }

        if ($user->getIsSuite()) {
          $result['roles'][] = 'suite';
        }

        if ($user->getIsSuiteSubscribed()) {
          $result['roles'][] = 'suite_subscriber';
        }

        if ($user->getIsSuiteDisabled()) {
          $result['roles'][] = 'suite_disabled';
        }

        $results[] = $result;
      }
    }

    return array(
      'data' => $results,
    );
  }
}
