<?php

final class PhabricatorMentionPHIDType extends PhabricatorPHIDType {

  const TYPECONST = 'MNTN';

  public function getTypeName() {
    return pht('Mention');
  }

  public function newObject() {
    return new PhabricatorMention();
  }

  public function getPHIDTypeApplicationClass() {
    return 'PhabricatorMentionApplication';
  }

  protected function buildQueryForObjects(
    PhabricatorObjectQuery $query,
    array $phids) {

    return id(new PhabricatorMentionQuery())
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
