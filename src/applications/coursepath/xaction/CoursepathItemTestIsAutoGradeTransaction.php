<?php

final class CoursepathItemTestIsAutoGradeTransaction
  extends CoursepathItemTestTransactionType {

  const TRANSACTIONTYPE = 'item.test.auto_grade';

  public function generateOldValue($object) {
    return $object->getIsNotAutomaticallyGraded();
  }

  public function applyInternalEffects($object, $value) {
    $object->setIsNotAutomaticallyGraded((int)$value);
  }

}
