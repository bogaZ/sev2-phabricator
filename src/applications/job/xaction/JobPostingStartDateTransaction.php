<?php

final class JobPostingStartDateTransaction
  extends JobPostingDateTransaction {

  const TRANSACTIONTYPE = 'job.startdate';

  public function generateOldValue($object) {
    $editor = $this->getEditor();

    return $object->newStartDateTime()
      ->newAbsoluteDateTime()
      ->toDictionary();
  }

  public function applyInternalEffects($object, $value) {
    $actor = $this->getActor();
    $editor = $this->getEditor();

    $datetime = PhutilCalendarAbsoluteDateTime::newFromDictionary($value);

    $object->setStartDateTime($datetime);
  }

  public function shouldHide() {
    if ($this->isCreateTransaction()) {
      return true;
    }

    return false;
  }

  public function getTitle() {
    return pht(
      '%s changed the start date for this job posting from %s to %s.',
      $this->renderAuthor(),
      $this->renderOldDate(),
      $this->renderNewDate());
  }

  public function getTitleForFeed() {
    return pht(
      '%s changed the start date for %s from %s to %s.',
      $this->renderAuthor(),
      $this->renderObject(),
      $this->renderOldDate(),
      $this->renderNewDate());
  }

  protected function getInvalidDateMessage() {
    return pht('Start date is invalid.');
  }

}
