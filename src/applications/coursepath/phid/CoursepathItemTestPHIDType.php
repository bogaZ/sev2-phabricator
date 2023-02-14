<?php

final class CoursepathItemTestPHIDType extends PhabricatorPHIDType {

  const TYPECONST = 'CRTS';

  public function getTypeName() {
    return pht('Coursepath Item Test');
  }

  public function newObject() {
    return new CoursepathItemTest();
  }

  public function getPHIDTypeApplicationClass() {
    return 'PhabricatorCoursepathApplication';
  }

  protected function buildQueryForObjects(
    PhabricatorObjectQuery $query,
    array $phids) {

    return id(new CoursepathItemQuery())
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
