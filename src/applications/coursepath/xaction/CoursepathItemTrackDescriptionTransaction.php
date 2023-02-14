<?php

final class CoursepathItemTrackDescriptionTransaction
  extends CoursepathItemTrackTransactionType {

  const TRANSACTIONTYPE = 'item.track.description';

  public function generateOldValue($object) {
    return $object->getDescription();
  }

  public function applyInternalEffects($object, $value) {
    $object->setDescription($value);
  }
}
