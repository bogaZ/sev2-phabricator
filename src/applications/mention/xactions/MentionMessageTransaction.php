<?php

final class MentionMessageTransaction
extends MentionTransactionType {
  const TRANSACTIONTYPE = 'mention:title';

  public function generateOldValue($object) {
    return $object->getMessage();
  }

  public function applyInternalEffects($object, $value) {
    $object->setMessage($value);
  }

  public function getTitle() {
    return pht(
      '%s add message from %s to %s.',
      $this->renderAuthor(),
      $this->renderOldValue(),
      $this->renderNewValue());
  }
}
