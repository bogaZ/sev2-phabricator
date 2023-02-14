<?php

final class JobPostingBenefitTransaction
  extends JobPostingTransactionType {

  const TRANSACTIONTYPE = 'job.benefit';

  public function generateOldValue($object) {
    return (int)$object->getBenefit();
  }

  public function applyInternalEffects($object, $value) {
    $object->setBenefit($value);
  }

  public function getTitle() {
    if ($this->getNewValue()) {
      return pht(
        '%s set benefit on this job posting.',
        $this->renderAuthor());
    }
  }

  public function getTitleForFeed() {
    if ($this->getNewValue()) {
      return pht(
        '%s updated benefit on %s.',
        $this->renderAuthor(),
        $this->renderObject());
    }
  }

}
