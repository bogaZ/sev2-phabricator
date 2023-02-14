<?php

final class TeachableConfigurationEmailTransaction
  extends TeachableConfigurationTransactionType {

  const TRANSACTIONTYPE = 'teachable.email';

  public function generateOldValue($object) {
    return $object->getEmail();
  }

  public function applyInternalEffects($object, $value) {
    $object->setEmail($value);
  }

  public function getTitle() {
    return pht(
      '%s changed this email from %s to %s.',
      $this->renderAuthor(),
      $this->renderOldValue(),
      $this->renderNewValue());
  }

  public function getTitleForFeed() {
    return pht(
      '%s changed %s teachable email from %s to %s.',
      $this->renderAuthor(),
      $this->renderObject(),
      $this->renderOldValue(),
      $this->renderNewValue());
  }

}
