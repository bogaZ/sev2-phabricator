<?php

final class SuiteProfileIsEligibleForJobTransaction
  extends SuiteProfileTransactionType {

  const TRANSACTIONTYPE = 'suite:profile-job';

  public function generateOldValue($object) {
    return (bool)$object->getIsEligibleForJob();
  }

  public function generateNewValue($object, $value) {
    return (bool)$value;
  }

  public function applyInternalEffects($object, $value) {
    if ($this->isNewObject()) {
      $object->setIsEligibleForJob(0);
    } else {
      $object->setIsEligibleForJob((int)$value);
    }
  }


  public function getTitle() {
    if ($this->getNewValue()) {
      return pht(
        '%s disabled Job eligible flag.',
        $this->renderAuthor());
    } else {
      return pht(
        '%s enabled Job eligible flag.',
        $this->renderAuthor());
    }

  }

}
