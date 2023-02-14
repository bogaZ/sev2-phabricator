<?php

final class JobPostingPHIDType extends PhabricatorPHIDType {

  const TYPECONST = 'JOBS';

  public function getTypeName() {
    return pht('Job Posting');
  }

  public function newObject() {
    return new JobPosting();
  }

  public function getPHIDTypeApplicationClass() {
    return 'PhabricatorJobApplication';
  }

  protected function buildQueryForObjects(
    PhabricatorObjectQuery $query,
    array $phids) {

    return id(new JobPostingQuery())
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
