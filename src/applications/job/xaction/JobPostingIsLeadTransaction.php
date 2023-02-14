<?php

final class JobPostingIsLeadTransaction
  extends JobPostingTransactionType {

  const TRANSACTIONTYPE = 'job.islead';

  public function generateOldValue($object) {
    return (int)$object->getIsLead();
  }

  public function generateNewValue($object, $value) {
    return (int)$value;
  }

  public function applyInternalEffects($object, $value) {
    $object->setIsLead($value);
  }

  public function getTitle() {
    if ($this->getNewValue()) {
      return pht(
        '%s mark this posting as lead.',
        $this->renderAuthor());
    } else {
      return pht(
        '%s removed lead flag from this posting.',
        $this->renderAuthor());
    }
  }

  public function getTitleForFeed() {
    if ($this->getNewValue()) {
      return pht(
        '%s marked %s as lead.',
        $this->renderAuthor(),
        $this->renderObject());
    } else {
      return pht(
        '%s remove lead flag from %s.',
        $this->renderAuthor(),
        $this->renderObject());
    }
  }

}
