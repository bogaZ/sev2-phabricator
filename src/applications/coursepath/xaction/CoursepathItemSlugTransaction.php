<?php

final class CoursepathItemSlugTransaction
  extends CoursepathItemTransactionType {

  const TRANSACTIONTYPE = 'item.slug';

  public function generateOldValue($object) {
    return $object->getSlug();
  }

  public function applyInternalEffects($object, $value) {
    $object->setSlug($value);
  }

  public function getTitle() {
    return pht(
      '%s renamed this slug from %s to %s.',
      $this->renderAuthor(),
      $this->renderOldValue(),
      $this->renderNewValue());
  }

  public function getTitleForFeed() {
    return pht(
      '%s renamed %s slug %s to %s.',
      $this->renderAuthor(),
      $this->renderObject(),
      $this->renderOldValue(),
      $this->renderNewValue());
  }

}
