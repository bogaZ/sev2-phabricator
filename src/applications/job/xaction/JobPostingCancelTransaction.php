<?php

final class JobPostingCancelTransaction
  extends JobPostingTransactionType {

  const TRANSACTIONTYPE = 'job.cancel';

  public function generateOldValue($object) {
    return (int)$object->getIsCancelled();
  }

  public function generateNewValue($object, $value) {
    return (int)$value;
  }

  public function applyInternalEffects($object, $value) {
    $object->setIsCancelled($value);
  }

  public function getTitle() {
    if ($this->getNewValue()) {
      return pht(
        '%s cancelled this job posting.',
        $this->renderAuthor());
    } else {
      return pht(
        '%s reinstated this job posting.',
        $this->renderAuthor());
    }
  }

  public function getTitleForFeed() {
    if ($this->getNewValue()) {
      return pht(
        '%s cancelled %s.',
        $this->renderAuthor(),
        $this->renderObject());
    } else {
      return pht(
        '%s reinstated %s.',
        $this->renderAuthor(),
        $this->renderObject());
    }
  }

}
