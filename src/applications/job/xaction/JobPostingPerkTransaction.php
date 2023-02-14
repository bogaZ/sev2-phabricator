<?php

final class JobPostingPerkTransaction
  extends JobPostingTransactionType {

  const TRANSACTIONTYPE = 'job.perk';

  public function generateOldValue($object) {
    return (int)$object->getPerk();
  }

  public function applyInternalEffects($object, $value) {
    $object->setPerk($value);
  }

  public function getTitle() {
    if ($this->getNewValue()) {
      return pht(
        '%s set perk on this job posting.',
        $this->renderAuthor());
    }
  }

  public function getTitleForFeed() {
    if ($this->getNewValue()) {
      return pht(
        '%s updated perk on %s.',
        $this->renderAuthor(),
        $this->renderObject());
    }
  }

}
