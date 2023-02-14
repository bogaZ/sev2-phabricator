<?php

final class PerformancePipPHIDType extends PhabricatorPHIDType {

  const TYPECONST = 'PPIP';

  public function getTypeName() {
    return pht('Performance Improvement Plan');
  }

  public function newObject() {
    return new PerformancePip();
  }

  public function getPHIDTypeApplicationClass() {
    return 'PhabricatorPerformanceApplication';
  }

  protected function buildQueryForObjects(
    PhabricatorObjectQuery $query,
    array $phids) {

    return id(new PerformancePipQuery())
      ->withPHIDs($phids);
  }

  public function loadHandles(
    PhabricatorHandleQuery $query,
    array $handles,
    array $objects) {

    $viewer = $query->getViewer();
    foreach ($handles as $phid => $handle) {
      $source = $objects[$phid];

      $handle->setName(pht('PIP of %s', $source->getTargetPHID()));
      $handle->setURI($source->getViewURI());
    }
  }

}
