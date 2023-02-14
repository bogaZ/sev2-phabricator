<?php

final class CoursepathItemTestSubmissionPHIDType
  extends PhabricatorPHIDType {

  const TYPECONST = 'CRSM';

  public function getTypeName() {
    return pht('Skill Test Submission');
  }

  public function newObject() {
    return new CoursepathItemTestSubmission();
  }

  public function getPHIDTypeApplicationClass() {
    return 'PhabricatorCoursepathApplication';
  }

  protected function buildQueryForObjects(
    PhabricatorObjectQuery $query,
    array $phids) {

    return id(new CoursepathItemTestSubmissionQuery())
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
