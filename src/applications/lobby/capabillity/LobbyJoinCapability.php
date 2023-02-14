<?php

final class LobbyJoinCapability
  extends PhabricatorPolicyCapability {

  const CAPABILITY = 'lobby.join';

  public function getCapabilityName() {
    return pht('Join Lobby Policy');
  }

  public function describeCapabilityRejection() {
    return pht(
      'You do not have permission to join lobby.');
  }

}
