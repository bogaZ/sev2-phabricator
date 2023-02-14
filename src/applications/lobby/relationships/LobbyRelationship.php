<?php

abstract class LobbyRelationship
  extends PhabricatorObjectRelationship {

  public function isEnabledForObject($object) {
    $viewer = $this->getViewer();

    $has_app = PhabricatorApplication::isClassInstalledForViewer(
      'PhabricatorLobbyApplication',
      $viewer);
    if (!$has_app) {
      return false;
    }

    return ($object instanceof LobbyState);
  }
}
