<?php

final class PerformancePipNoteTransaction
  extends PerformancePipTransactionType {

  const TRANSACTIONTYPE = 'performance:pip-note';

  public function generateOldValue($object) {
    return $object->getNote();
  }

  public function generateNewValue($object, $value) {
    // NOTE: perhaps need to validate
    return $value;
  }

  public function applyInternalEffects($object, $value) {
    $object->setNote($value);
  }

  public function getTitle() {
    return pht(
      '%s changed note from %s to %s.',
      $this->renderObject(),
      $this->renderOldValue(),
      $this->renderNewValue());
  }

}
