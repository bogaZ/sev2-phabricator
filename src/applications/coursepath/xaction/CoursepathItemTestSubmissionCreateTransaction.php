<?php

final class CoursepathItemTestSubmissionCreateTransaction
  extends CoursepathItemTestTransactionType {

  const TRANSACTIONTYPE = 'coursepath.test.submission';

  public function generateOldValue($object) {
    return null;
  }

  public function applyExternalEffects($object, $values) {
    $submission = CoursepathItemTestSubmission::initializeNewSubmission(
      $this->getActor(),
      $values['user_phid'],
      $object->getPHID(),
      $values['answer'],
      $values['score'],
      $values['session']);
    $submission->save();
    return;
  }

  public function getTitle() {
    $new = $this->getNewValue();
    if (!is_array($new)) {
      $new = array();
    }
    $handles = $this->renderHandleList($new);
    return pht(
      '%s submit %s new skill test submission(s): %s.',
      $this->renderAuthor(),
      new PhutilNumber(count($new)),
      $handles);
  }

  public function getTitleForFeed() {
    $new = $this->getNewValue();
    if (!is_array($new)) {
      $new = array();
    }
    $handles = $this->renderHandleList($new);
    return pht(
      '%s submiteed %s new skill test submission(s): %s.',
      $this->renderAuthor(),
      $this->renderObject(),
      $handles);
  }

  public function getIcon() {
    return 'fa-user-plus';
  }

}
