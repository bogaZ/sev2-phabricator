<?php

final class CoursepathItemTestTypeTransaction
  extends CoursepathItemTestTransactionType {

  const TRANSACTIONTYPE = 'item.test.type';

  public function generateOldValue($object) {
    return $object->getType();
  }

  public function applyInternalEffects($object, $value) {
    $object->setType($value);
  }

  public function getTitle() {
    return pht(
      '%s updated the type.',
      $this->renderAuthor());
  }

  public function getTitleForFeed() {
    return pht(
      '%s updated the type for %s.',
      $this->renderAuthor(),
      $this->renderObject());
  }
}
