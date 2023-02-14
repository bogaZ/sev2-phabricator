<?php

final class PhabricatorCoursepathTestSuiteTypeTransaction
  extends CoursepathItemTestTransactionType {

  const TRANSACTIONTYPE = 'item.test.suite_type';

  public function generateOldValue($object) {
    return $object->getSuiteType();
  }

  public function applyInternalEffects($object, $value) {
    $object->setSuiteType($value);
  }

  public function getTitle() {
    return pht(
      '%s changed this suite type from %s to %s.',
      $this->renderAuthor(),
      $this->renderOldValue(),
      $this->renderNewValue());
  }

  public function getTitleForFeed() {
    return pht(
      '%s changed %s suite type %s to %s.',
      $this->renderAuthor(),
      $this->renderObject(),
      $this->renderOldValue(),
      $this->renderNewValue());
  }

}
