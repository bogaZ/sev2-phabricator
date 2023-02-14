<?php

final class CoursepathItemTestSeverityTransaction
  extends CoursepathItemTestTransactionType {

  const TRANSACTIONTYPE = 'item.test.severity';

  public function generateOldValue($object) {
    return $object->getSeverity();
  }

  public function applyInternalEffects($object, $value) {
    $object->setSeverity($value);
  }

  public function getTitle() {
    return pht(
      '%s updated the severity.',
      $this->renderAuthor());
  }

  public function getTitleForFeed() {
    return pht(
      '%s updated the severity for %s.',
      $this->renderAuthor(),
      $this->renderObject());
  }
}
