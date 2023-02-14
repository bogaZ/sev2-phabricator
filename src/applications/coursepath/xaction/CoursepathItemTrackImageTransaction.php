<?php

final class CoursepathItemTrackImageTransaction
  extends CoursepathItemTrackTransactionType {

  const TRANSACTIONTYPE = 'item.track.image';

  public function generateOldValue($object) {
    return $object->getImage();
  }

  public function applyInternalEffects($object, $value) {
    $object->setImage($value);
  }
}
