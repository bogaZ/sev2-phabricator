<?php

final class ConpherenceThreadIsHQTransaction
  extends ConpherenceThreadTransactionType {

  const TRANSACTIONTYPE = 'is-hq';

  public function generateOldValue($object) {
    return (int)$object->getIsHQ();
  }

  public function generateNewValue($object, $value) {
    return (int)$value;
  }

  public function applyInternalEffects($object, $value) {
    $object->setIsHQ($value);
  }

  public function getTitle() {
    if ($this->getNewValue()) {
      return pht(
        '%s set this channel as HQ.',
        $this->renderAuthor());
    } else {
      return pht(
        '%s unset this channel as HQ.',
        $this->renderAuthor());
    }
  }

}
