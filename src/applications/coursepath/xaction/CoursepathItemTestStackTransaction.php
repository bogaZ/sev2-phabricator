<?php

final class CoursepathItemTestStackTransaction
  extends CoursepathItemTestTransactionType {

  const TRANSACTIONTYPE = 'item.test.stack';

  public function generateOldValue($object) {
    return $object->getStack();
  }

  public function applyInternalEffects($object, $value) {
    $object->setStack($value);
  }

}
