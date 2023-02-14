<?php

final class CoursepathItemTestTestCodeTransaction
  extends CoursepathItemTestTransactionType {

  const TRANSACTIONTYPE = 'item.test.test_code';

  public function generateOldValue($object) {
    return $object->getTestCode();
  }

  public function applyInternalEffects($object, $value) {
    $object->setTestCode($value);
  }

  public function getTitle() {
    return pht(
      '%s changed this test code from %s to %s.',
      $this->renderAuthor(),
      $this->renderOldValue(),
      $this->renderNewValue());
  }

  public function getTitleForFeed() {
    return pht(
      '%s changed %s skill test code from %s to %s.',
      $this->renderAuthor(),
      $this->renderObject(),
      $this->renderOldValue(),
      $this->renderNewValue());
  }

  public function validateTransactions($object, array $xactions) {
    $errors = array();

    $max_length = (int) $object->getColumnMaximumByteLength('testCode');
    foreach ($xactions as $xaction) {
      $new_value = $xaction->getNewValue();
      $new_length = strlen($new_value);
      if ($max_length < $new_length) {
        $errors[] = $this->newInvalidError(
          pht('The testCode can be no longer than %s characters.',
          new PhutilNumber($max_length)));
      }
    }

    return $errors;
  }

}
