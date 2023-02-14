<?php

final class CoursepathItemTrackNameTransaction
  extends CoursepathItemTrackTransactionType {

  const TRANSACTIONTYPE = 'item.track.name';

  public function generateOldValue($object) {
    return $object->getName();
  }

  public function applyInternalEffects($object, $value) {
    $object->setName($value);
  }
}
