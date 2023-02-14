<?php

final class JobPostingStackTransaction
  extends JobPostingTransactionType {

  const TRANSACTIONTYPE = 'job.stack';

  public function generateOldValue($object) {
    return $object->getStack();
  }

  public function applyInternalEffects($object, $value) {
    $object->setStack($value);
  }

}
