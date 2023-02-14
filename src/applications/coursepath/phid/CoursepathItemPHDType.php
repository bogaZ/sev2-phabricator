<?php

final class CoursepathItemPHIDType extends PhabricatorPHIDType {

  const TYPECONST = 'CRSI';

  public function getTypeName() {
    return pht('Coursepath Item');
  }

  public function newObject() {
    return new CoursepathItem();
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
