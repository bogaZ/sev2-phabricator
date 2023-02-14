<?php

final class TeachableConfigurationURLTransaction
  extends TeachableConfigurationTransactionType {

  const TRANSACTIONTYPE = 'teachable.url';

  public function generateOldValue($object) {
    return $object->getUrl();
  }

  public function applyInternalEffects($object, $value) {
    $object->setUrl($value);
  }

  public function getTitle() {
    return pht(
      '%s changed this url from %s to %s.',
      $this->renderAuthor(),
      $this->renderOldValue(),
      $this->renderNewValue());
  }

  public function getTitleForFeed() {
    return pht(
      '%s changed %s teachable url from %s to %s.',
      $this->renderAuthor(),
      $this->renderObject(),
      $this->renderOldValue(),
      $this->renderNewValue());
  }

}
