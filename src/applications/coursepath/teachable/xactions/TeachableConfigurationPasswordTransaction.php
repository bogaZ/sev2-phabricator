<?php

final class TeachableConfigurationPasswordTransaction
  extends TeachableConfigurationTransactionType {

  const TRANSACTIONTYPE = 'teachable.password';

  public function generateOldValue($object) {
    return $object->getPassword();
  }

  public function applyInternalEffects($object, $value) {
    $object->setPassword($value);
  }

  public function getTitle() {
    return pht(
      '%s changed this password from %s to %s.',
      $this->renderAuthor(),
      $this->renderOldValue(),
      $this->renderNewValue());
  }

  public function getTitleForFeed() {
    return pht(
      '%s changed %s teachable password from %s to %s.',
      $this->renderAuthor(),
      $this->renderObject(),
      $this->renderOldValue(),
      $this->renderNewValue());
  }

}
