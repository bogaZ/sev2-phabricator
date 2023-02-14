<?php

final class LobbyStatePHIDType extends PhabricatorPHIDType {

  const TYPECONST = 'LBYS';

  public function getTypeName() {
    return pht('Lobby State');
  }

  public function newObject() {
    return new LobbyState();
  }

  public function getPHIDTypeApplicationClass() {
    return 'PhabricatorLobbyApplication';
  }

  protected function buildQueryForObjects(
    PhabricatorObjectQuery $query,
    array $phids) {

    return id(new LobbyStateQuery())
      ->withPHIDs($phids);
  }

  public function loadHandles(
    PhabricatorHandleQuery $query,
    array $handles,
    array $objects) {

    $viewer = $query->getViewer();
    foreach ($handles as $phid => $handle) {
      $state = $objects[$phid];

      $handle->setName(pht('%s', $state->loadUser()->getRealname()));
      $handle->setURI($state->getViewURI());
    }
  }

}
