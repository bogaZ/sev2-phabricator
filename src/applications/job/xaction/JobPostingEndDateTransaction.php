<?php

final class JobPostingEndDateTransaction
  extends JobPostingDateTransaction {

  const TRANSACTIONTYPE = 'job.enddate';

  public function generateOldValue($object) {
    $editor = $this->getEditor();

    return $object->newEndDateTimeForEdit()
      ->newAbsoluteDateTime()
      ->toDictionary();
  }

  public function applyInternalEffects($object, $value) {
    $actor = $this->getActor();
    $editor = $this->getEditor();

    $datetime = PhutilCalendarAbsoluteDateTime::newFromDictionary($value);

    $object->setEndDateTime($datetime);
  }

  public function shouldHide() {
    if ($this->isCreateTransaction()) {
      return true;
    }

    return false;
  }

  public function getTitle() {
    return pht(
      '%s changed the end date for this job posting from %s to %s.',
      $this->renderAuthor(),
      $this->renderOldDate(),
      $this->renderNewDate());
  }

  public function getTitleForFeed() {
    return pht(
      '%s changed the end date for %s job posting %s to %s.',
      $this->renderAuthor(),
      $this->renderObject(),
      $this->renderOldDate(),
      $this->renderNewDate());
  }

  protected function getInvalidDateMessage() {
    return pht('End date is invalid.');
  }

}
