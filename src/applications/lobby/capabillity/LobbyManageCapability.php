<?php

final class LobbyManageCapability
  extends PhabricatorPolicyCapability {

  const CAPABILITY = 'lobby.manage';

  public function getCapabilityName() {
    return pht('Manage Lobby Policy');
  }

  public function describeCapabilityRejection() {
    return pht(
      'You do not have permission to manage lobby.');
  }

}
