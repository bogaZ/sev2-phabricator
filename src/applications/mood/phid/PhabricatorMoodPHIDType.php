<?php

final class PhabricatorMoodPHIDType extends PhabricatorPHIDType {

  const TYPECONST = 'MOOD';

  public function getTypeName() {
    return pht('Mood');
  }

  public function newObject() {
    return new PhabricatorMood();
  }

  public function getPHIDTypeApplicationClass() {
    return 'PhabricatorMoodApplication';
  }

  protected function buildQueryForObjects(
    PhabricatorObjectQuery $query,
    array $phids) {

    return id(new PhabricatorMoodQuery())
      ->withPHIDs($phids);
  }

  public function loadHandles(
    PhabricatorHandleQuery $query,
    array $handles,
    array $objects) {

    foreach ($handles as $phid => $handle) {
      $identifier = $objects[$phid];
    }
  }
}
