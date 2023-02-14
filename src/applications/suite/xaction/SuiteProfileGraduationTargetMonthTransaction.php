<?php

final class SuiteProfileGraduationTargetMonthTransaction
  extends SuiteProfileTransactionType {

  const TRANSACTIONTYPE = 'suite:profile-commitment';

  public function generateOldValue($object) {
    return $object->getGraduationTargetMonth();
  }

  public function generateNewValue($object, $value) {
    return (int)$value;
  }

  public function applyInternalEffects($object, $value) {
    $object->setGraduationTargetMonth($value);
  }

  public function getTitle() {
    return pht(
      '%s changed commitment from %d to %d months.',
      $this->renderObject(),
      $this->renderOldValue(),
      $this->renderNewValue());
  }

}
