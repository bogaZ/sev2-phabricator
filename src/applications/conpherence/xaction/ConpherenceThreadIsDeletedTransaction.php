<?php

final class ConpherenceThreadIsDeletedTransaction
  extends ConpherenceThreadTransactionType {

    const TRANSACTIONTYPE = 'is-deleted';

    public function generateOldValue($object) {
      return (int)$object->getIsDeleted();
    }

    public function generateNewValue($object, $value) {
      return (int)$value;
    }

    public function applyInternalEffects($object, $value) {
      $object->setIsDeleted($value);
    }

    public function getTitle() {
      $new = $this->getNewValue();

      return pht(
        '%s has %s this room', $this->renderAuthor(),
        $new?'archived':'unarchived');
    }
}
