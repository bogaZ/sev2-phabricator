<?php

final class TeachableConfigurationPHIDType
  extends PhabricatorPHIDType {

  const TYPECONST = 'TCHB';

  public function getTypeName() {
    return pht('Teachable Configuration');
  }

  public function newObject() {
    return new TeachableConfiguration();
  }

  public function getPHIDTypeApplicationClass() {
    return 'PhabricatorCoursepathApplication';
  }

  protected function buildQueryForObjects(
    PhabricatorObjectQuery $query,
    array $phids) {

    return id(new TeachableConfigurationQuery())
      ->withPHIDs($phids);
  }

  public function loadHandles(
    PhabricatorHandleQuery $query,
    array $handles,
    array $objects) {

    $viewer = $query->getViewer();
    foreach ($handles as $phid => $handle) {
      $source = $objects[$phid];

      $handle->setName($source->getName());
      $handle->setURI($source->getViewURI());
    }
  }

}
