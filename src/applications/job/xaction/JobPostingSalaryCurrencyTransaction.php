<?php

final class JobPostingSalaryCurrencyTransaction
  extends JobPostingTransactionType {

  const TRANSACTIONTYPE = 'job.salary.currency';

  public function generateOldValue($object) {
    return $object->getSalaryCurrency();
  }

  public function applyInternalEffects($object, $value) {
    $object->setSalaryCurrency($value);
  }

  public function getTitle() {
    if ($this->getNewValue()) {
      return pht(
        '%s set currency on this job posting.',
        $this->renderAuthor());
    }
  }

  public function getTitleForFeed() {
    if ($this->getNewValue()) {
      return pht(
        '%s updated currency on %s.',
        $this->renderAuthor(),
        $this->renderObject());
    }
  }

}
