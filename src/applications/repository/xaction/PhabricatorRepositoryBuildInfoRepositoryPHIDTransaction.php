<?php

final
  class PhabricatorRepositoryBuildInfoRepositoryPHIDTransaction
  extends
    PhabricatorRepositoryBuildInfoTransactionType {

  const TRANSACTIONTYPE = 'repository:build:repositoryPHID';

  public function generateOldValue($object) {
    return $object->getRepositoryPHID();
  }

  public function applyInternalEffects($object, $value) {
    $object->setRepositoryPHID($value);
  }
}
