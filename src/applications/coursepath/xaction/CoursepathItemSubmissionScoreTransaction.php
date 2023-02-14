<?php

final class CoursepathItemSubmissionScoreTransaction
  extends CoursepathItemTestSubmissionTransactionType {

  const TRANSACTIONTYPE = 'item.submission.score';

  public function generateOldValue($object) {
    return $object->getScore();
  }

  public function applyInternalEffects($object, $value) {
    $object->setScore($value);
  }
}
