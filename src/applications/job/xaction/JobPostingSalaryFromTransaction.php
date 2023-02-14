<?php

final class JobPostingSalaryFromTransaction
  extends JobPostingTransactionType {

  const TRANSACTIONTYPE = 'job.salary.from';

  public function generateOldValue($object) {
    return (int)$object->getSalaryFrom();
  }

  public function generateNewValue($object, $value) {
    return (int)$value;
  }

  public function applyInternalEffects($object, $value) {
    $object->setSalaryFrom($value);
  }

  public function getTitle() {
    if ($this->getNewValue()) {
      return pht(
        '%s set new start salary on this job posting.',
        $this->renderAuthor());
    }
  }

  public function getTitleForFeed() {
    if ($this->getNewValue()) {
      return pht(
        '%s updated start salary on %s.',
        $this->renderAuthor(),
        $this->renderObject());
    }
  }

}
