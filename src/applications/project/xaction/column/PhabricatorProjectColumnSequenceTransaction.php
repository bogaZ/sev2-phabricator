<?php

final class PhabricatorProjectColumnSequenceTransaction
  extends PhabricatorProjectColumnTransactionType {

  const TRANSACTIONTYPE = 'project:col:sequence';

  public function generateOldValue($object) {
    return $object->getSequence();
  }

  public function applyInternalEffects($object, $value) {
    $object->setSequence($value);
  }

}