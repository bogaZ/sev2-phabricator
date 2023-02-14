<?php

final class CoursepathItemTrackPHIDType extends PhabricatorPHIDType {

  const TYPECONST = 'CRTK';

  public function getTypeName() {
    return pht('Coursepath Item Track | teachable');
  }

  public function newObject() {
    return new CoursepathItemTrack();
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
