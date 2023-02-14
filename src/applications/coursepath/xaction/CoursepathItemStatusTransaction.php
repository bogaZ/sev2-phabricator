<?php

final class CoursepathItemStatusTransaction
  extends CoursepathItemTransactionType {

  const TRANSACTIONTYPE = 'item.status';

  public function generateOldValue($object) {
    return $object->getStatus();
  }

  public function applyInternalEffects($object, $value) {
    $object->setStatus($value);
  }

  public function getTitle() {
    if ($this->getNewValue() == CoursepathItem::STATUS_ARCHIVED) {
      return pht(
        '%s disabled this course path.',
        $this->renderAuthor());
    } else {
      return pht(
        '%s enabled this course path.',
        $this->renderAuthor());
    }
  }

  public function getTitleForFeed() {
    if ($this->getNewValue() == CoursepathItem::STATUS_ARCHIVED) {
      return pht(
        '%s disabled the course path %s.',
        $this->renderAuthor(),
        $this->renderObject());
    } else {
      return pht(
        '%s enabled the course path %s.',
        $this->renderAuthor(),
        $this->renderObject());
    }
  }

  public function getIcon() {
    if ($this->getNewValue() == CoursepathItem::STATUS_ARCHIVED) {
      return 'fa-ban';
    } else {
      return 'fa-check';
    }
  }

}
