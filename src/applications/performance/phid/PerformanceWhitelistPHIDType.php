<?php

final class PerformanceWhitelistPHIDType extends PhabricatorPHIDType {

  const TYPECONST = 'PWHI';

  public function getTypeName() {
    return pht('Performance Whitelist PHID');
  }

  public function newObject() {
    return new PerformanceWhitelist();
  }

  public function getPHIDTypeApplicationClass() {
    return 'PhabricatorPerformanceApplication';
  }

  protected function buildQueryForObjects(
    PhabricatorObjectQuery $query,
    array $phids) {

    return id(new PerformanceWhitelistQuery())
      ->withPHIDs($phids);
  }

  public function loadHandles(
    PhabricatorHandleQuery $query,
    array $handles,
    array $objects) {

    $viewer = $query->getViewer();
    foreach ($handles as $phid => $handle) {
      $source = $objects[$phid];

      $handle->setName(pht('Whitelisted %s', $source->getOwnerPHID()));
      $handle->setURI($source->getViewURI());
    }
  }

}
