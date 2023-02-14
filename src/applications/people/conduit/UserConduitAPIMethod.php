<?php

abstract class UserConduitAPIMethod extends ConduitAPIMethod {

  final public function getApplication() {
    return PhabricatorApplication::getByClass('PhabricatorPeopleApplication');
  }

  protected function buildUserInformationDictionary(
    PhabricatorUser $user,
    $with_email = false,
    $with_availability = false,
    $with_phone_number = false,
    $enrollment = null,
    $with_lobby_state = false,
    $project_phids = null) {
    $roles = array();
    $obj = array();
    if ($user->getIsDisabled()) {
      $roles[] = 'disabled';
    }
    if ($user->getIsSystemAgent()) {
      $roles[] = 'agent';
    }
    if ($user->getIsMailingList()) {
      $roles[] = 'list';
    }
    if ($user->getIsAdmin()) {
      $roles[] = 'admin';
    }

    $primary = $user->loadPrimaryEmail();
    if ($primary && $primary->getIsVerified()) {
      $email = $primary->getAddress();
      $roles[] = 'verified';
    } else {
      $email = null;
      $roles[] = 'unverified';
    }

    if ($user->getIsApproved()) {
      $roles[] = 'approved';
    }

    if ($user->isUserActivated()) {
      $roles[] = 'activated';
    }

    if ($user->getIsConnect()) {
      $roles[] = 'connect';
    }

    if ($user->getIsForDev()) {
      $roles[] = 'dev';
    }

    if ($user->getIsSuite()) {
      $roles[] = 'suite';
    }

    if ($user->getIsSuiteSubscribed()) {
      $roles[] = 'suite_subscriber';
    }

    if ($user->getIsSuiteDisabled()) {
      $roles[] = 'suite_disabled';
    }

    if ($user->getCustomRoles() !== null) {
      $string = $user->getCustomRoles();
      $obj = json_decode($string);
    }
    $sev2_roles_map = Sev2Roles::getSev2RolesMap();
    // empower user with custom roles

    foreach ($sev2_roles_map as $key => $value) {
      if (in_array($key, $obj)) {
        $roles[] = $value;
      }
    }

    $ph_checkin = new PhabricatorUserCheckIn();
    $table = $ph_checkin->getTableName();
    $conn_w = $ph_checkin->establishConnection('w');
    $date_now = PhabricatorTime::getNow();

    $last_checkin = queryfx_one(
      $conn_w,
      'SELECT max(dateCreated) as lastCheckin FROM %T where phid = %s ',
      sev2table($table),
      $user->getPHID());

    $return = array(
      'phid'         => $user->getPHID(),
      'userName'     => $user->getUserName(),
      'realName'     => $user->getRealName(),
      'image'        => $user->getProfileImageURI(),
      'uri'          => PhabricatorEnv::getURI('/p/'.$user->getUsername().'/'),
      'roles'        => $roles,
      'itemPHID'     => $enrollment,
      'groupPHIDs'   => $project_phids,
      'jid'          => $user->getJid(),
      'lastCheckin'  => (int)$last_checkin['lastCheckin'],
    );

    if ($with_email) {
      $return['primaryEmail'] = $email;
    }

    if ($with_phone_number) {
      $return['phoneNumber'] = $user->loadUserProfile()->getPhoneNumber();
    }

    if ($with_lobby_state) {
      $lobby_state = id(new LobbyStateQuery())
        ->setViewer($user)
        ->withOwnerPHIDs(array($user->getPHID()))
        ->executeOne();
      $return['state'] = null;
      if ($lobby_state) {
        $statuses = LobbyState::getStatusMap();
        $return['state'] = array(
          'status' => $statuses[$lobby_state->getStatus()],
          'currentTask' => $lobby_state->getCurrentTask(),
          'currentChannel' => $lobby_state->getCurrentChannel(),
        );
      }
    }

    if ($with_availability) {
      // TODO: Modernize this once we have a more long-term view of what the
      // data looks like.
      $until = $user->getAwayUntil();
      if ($until) {
        $return['currentStatus'] = 'away';
        $return['currentStatusUntil'] = $until;
      }
    }

    return $return;
  }

}
