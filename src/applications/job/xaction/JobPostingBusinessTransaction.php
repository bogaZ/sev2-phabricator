<?php

final class JobPostingBusinessTransaction
  extends JobPostingTransactionType {

  const TRANSACTIONTYPE = 'job.business';

  public function generateOldValue($object) {
    return $object->getBusiness();
  }

  public function applyInternalEffects($object, $value) {
    $object->setBusiness($value);
  }

}
