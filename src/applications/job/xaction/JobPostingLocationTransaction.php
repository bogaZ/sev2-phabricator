<?php

final class JobPostingLocationTransaction
  extends JobPostingTransactionType {

  const TRANSACTIONTYPE = 'job.location';

  public function generateOldValue($object) {
    return $object->getLocation();
  }

  public function applyInternalEffects($object, $value) {
    $object->setLocation($value);
  }

  public function getTitle() {
    return pht(
      '%s change this location from %s to %s.',
      $this->renderAuthor(),
      $this->renderOldValue(),
      $this->renderNewValue());
  }

  public function getTitleForFeed() {
    return pht(
      '%s changed %s job location from %s to %s.',
      $this->renderAuthor(),
      $this->renderObject(),
      $this->renderOldValue(),
      $this->renderNewValue());
  }

  public function validateTransactions($object, array $xactions) {
    $errors = array();

    if ($this->isEmptyTextTransaction($object->getName(), $xactions)) {
      $errors[] = $this->newRequiredError(
        pht('Job posting must have a location.'));
    }

    $max_length = (int)$object->getColumnMaximumByteLength('location');
    foreach ($xactions as $xaction) {
      $new_value = $xaction->getNewValue();
      $new_length = strlen($new_value);
      if ($new_length > $max_length) {
        $errors[] = $this->newInvalidError(
          pht('The location value can be no longer than %s characters.',
          new PhutilNumber($max_length)));
      }
    }

    return $errors;
  }

}
