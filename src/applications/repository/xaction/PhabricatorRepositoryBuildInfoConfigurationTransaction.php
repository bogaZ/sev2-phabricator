<?php

final class PhabricatorRepositoryBuildInfoConfigurationTransaction
  extends PhabricatorRepositoryBuildInfoTransactionType {

  const TRANSACTIONTYPE = 'repository:build:configuration';

  public function generateOldValue($object) {
    return $object->getConfiguration();
  }

  public function applyInternalEffects($object, $value) {
    if (is_array($value)) {
        $value = htmlspecialchars(json_encode($value), ENT_QUOTES, 'UTF-8');
    }
    $object->setConfiguration($value);
  }
}
