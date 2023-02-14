<?php

final class PhabricatorLobbyConfigOptions
  extends PhabricatorApplicationConfigOptions {

  public function getName() {
    return pht('Lobby Namespace');
  }

  public function getDescription() {
    return pht('Configure the Lobby namespace.');
  }

  public function getIcon() {
    return 'fa-heartbeat';
  }

  public function getGroup() {
    return 'core';
  }

  public function getOptions() {
    return array(
      $this->newOption('lobby.namespace', 'string', 'suite')
        ->setDescription(
          pht('Sets the namespace of the lobby. This normally will be your company name.'))
        ->setLocked(true),
    );
  }

}
