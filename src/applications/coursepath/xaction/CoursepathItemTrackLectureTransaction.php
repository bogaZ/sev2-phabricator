<?php

final class CoursepathItemTrackLectureTransaction
  extends CoursepathItemTrackTransactionType {

  const TRANSACTIONTYPE = 'item.track.lecture';

  public function generateOldValue($object) {
    return $object->getLecture();
  }

  public function applyInternalEffects($object, $value) {
    if ($value) {
        $value = json_encode($value);
    }
    $object->setLecture($value);
  }
}
