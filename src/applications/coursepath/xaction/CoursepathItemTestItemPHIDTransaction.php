<?php

final class CoursepathItemTestItemPHIDTransaction
  extends CoursepathItemTestTransactionType {

  const TRANSACTIONTYPE = 'item.test.itemPHID';

  public function generateOldValue($object) {
    return $object->getItemPHID();
  }

  public function applyInternalEffects($object, $value) {
    $object->setItemPHID($value);
  }
}
