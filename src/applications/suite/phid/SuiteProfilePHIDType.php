<?php

final class SuiteProfilePHIDType extends PhabricatorPHIDType {

  const TYPECONST = 'SUTP';

  public function getTypeName() {
    return pht('Suite Profile');
  }

  public function newObject() {
    return new SuiteProfile();
  }

  public function getPHIDTypeApplicationClass() {
    return 'PhabricatorSuiteApplication';
  }

  protected function buildQueryForObjects(
    PhabricatorObjectQuery $query,
    array $phids) {

    return id(new SuiteProfileQuery())
      ->withPHIDs($phids);
  }

  public function loadHandles(
    PhabricatorHandleQuery $query,
    array $handles,
    array $objects) {

    $viewer = $query->getViewer();
    foreach ($handles as $phid => $handle) {
      $source = $objects[$phid];

      $handle->setName(pht('Suite profile of %s', $source->getOwnerPHID()));
      $handle->setURI($source->getViewURI());
    }
  }

}
