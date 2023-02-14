<?php

final class LobbyStickitPHIDType extends PhabricatorPHIDType {

  const TYPECONST = 'LBYT';

  public function getTypeName() {
    return pht('Lobby Stickit');
  }

  public function newObject() {
    return new LobbyStickit();
  }

  public function getPHIDTypeApplicationClass() {
    return 'PhabricatorLobbyApplication';
  }

  protected function buildQueryForObjects(
    PhabricatorObjectQuery $query,
    array $phids) {

    return id(new LobbyStickitQuery())
      ->withPHIDs($phids);
  }

  public function loadHandles(
    PhabricatorHandleQuery $query,
    array $handles,
    array $objects) {

    $viewer = $query->getViewer();
    foreach ($handles as $phid => $handle) {
      $stickit = $objects[$phid];

      $handle->setName(pht('%s', $stickit->getTitle()));
      $handle->setURI($stickit->getViewURI());
    }
  }

}
