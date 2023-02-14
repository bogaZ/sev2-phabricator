<?php

final class PhabricatorRepositoryBuildInfoPHIDType
  extends PhabricatorPHIDType {

  const TYPECONST = 'RBUI';

  public function getTypeName() {
    return pht('Repository Build Info');
  }

  public function newObject() {
    return new PhabricatorRepositoryIdentity();
  }

  public function getPHIDTypeApplicationClass() {
    return 'PhabricatorDiffusionApplication';
  }

  protected function buildQueryForObjects(
    PhabricatorObjectQuery $query,
    array $phids) {

    return id(new PhabricatorRepositoryBuildInfoQuery())
      ->withPHIDs($phids);
  }

  public function loadHandles(
    PhabricatorHandleQuery $query,
    array $handles,
    array $objects) {}
}
