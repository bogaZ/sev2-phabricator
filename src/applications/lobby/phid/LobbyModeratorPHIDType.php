<?php

final class LobbyModeratorPHIDType extends PhabricatorPHIDType {

  const TYPECONST = 'LBYM';

  public function getTypeName() {
    return pht('Lobby Moderator');
  }

  public function newObject() {
    return new LobbyModerator();
  }

  public function getPHIDTypeApplicationClass() {
    return 'PhabricatorLobbyApplication';
  }

  protected function buildQueryForObjects(
    PhabricatorObjectQuery $query,
    array $phids) {

    return id(new LobbyModeratorQuery())
      ->withPHIDs($phids);
  }

  public function loadHandles(
    PhabricatorHandleQuery $query,
    array $handles,
    array $objects) {

    $viewer = $query->getViewer();
    foreach ($handles as $phid => $handle) {
      $moderator = $objects[$phid];

      $handle->setName(pht('Moderator of %s', $moderator->getChannelPHID()));
      $handle->setURI($moderator->getViewURI());
    }
  }

}
