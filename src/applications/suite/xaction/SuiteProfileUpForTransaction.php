<?php

final class SuiteProfileUpForTransaction
  extends SuiteProfileTransactionType {

  const TRANSACTIONTYPE = 'suite:profile-upfor';

  public function generateOldValue($object) {
    return $object->getUpFor();
  }

  public function generateNewValue($object, $value) {
    // NOTE: perhaps need to validate
    return $value;
  }

  public function applyInternalEffects($object, $value) {
    $object->setUpFor($value);
  }

  public function getTitle() {
    return pht(
      '%s changed interest from %s to %s.',
      $this->renderObject(),
      $this->renderOldValue(),
      $this->renderNewValue());
  }

}
