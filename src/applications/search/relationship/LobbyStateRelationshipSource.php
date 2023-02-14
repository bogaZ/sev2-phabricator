<?php

final class LobbyStateRelationshipSource
  extends PhabricatorObjectRelationshipSource {

  public function isEnabledForObject($object) {
    $viewer = $this->getViewer();

    return PhabricatorApplication::isClassInstalledForViewer(
      'PhabricatorLobbyApplication',
      $viewer);
  }

  public function getResultPHIDTypes() {
    return array(
      LobbyStatePHIDType::TYPECONST,
    );
  }

}
