<?php

final class SuiteBalancePHIDType extends PhabricatorPHIDType {

  const TYPECONST = 'SUTB';

  public function getTypeName() {
    return pht('Suite Balance');
  }

  public function newObject() {
    return new SuiteBalance();
  }

  public function getPHIDTypeApplicationClass() {
    return 'PhabricatorSuiteApplication';
  }

  protected function buildQueryForObjects(
    PhabricatorObjectQuery $query,
    array $phids) {

    return id(new SuiteBalanceQuery())
      ->withPHIDs($phids);
  }

  public function loadHandles(
    PhabricatorHandleQuery $query,
    array $handles,
    array $objects) {

    $viewer = $query->getViewer();
    foreach ($handles as $phid => $handle) {
      $source = $objects[$phid];

      $handle->setName(pht('Suite balance of %s', $source->getOwnerPHID()));
      $handle->setURI($source->getViewURI());
    }
  }

}
