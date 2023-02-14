<?php

final class JobPostingSalaryToTransaction
  extends JobPostingTransactionType {

  const TRANSACTIONTYPE = 'job.salary.to';

  public function generateOldValue($object) {
    return (int)$object->getSalaryTo();
  }

  public function generateNewValue($object, $value) {
    return (int)$value;
  }

  public function applyInternalEffects($object, $value) {
    $object->setSalaryTo($value);
  }

  public function getTitle() {
    if ($this->getNewValue()) {
      return pht(
        '%s set new end salary on this job posting.',
        $this->renderAuthor());
    }
  }

  public function getTitleForFeed() {
    if ($this->getNewValue()) {
      return pht(
        '%s updated end salary on %s.',
        $this->renderAuthor(),
        $this->renderObject());
    }
  }

}
