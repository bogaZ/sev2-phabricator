<?php

final class JobPostingTargetHiringTransaction
  extends JobPostingTransactionType {

  const TRANSACTIONTYPE = 'job.target_hiring';

  public function generateOldValue($object) {
    return $object->getTargetHiring();
  }

  public function applyInternalEffects($object, $value) {
    $object->setTargetHiring($value);
  }

}
