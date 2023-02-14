<?php

final class PhabricatorProjectEndDateTransaction
  extends PhabricatorProjectDateTransaction {

  const TRANSACTIONTYPE = 'project:enddate';

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
      '%s changed the end date for this project from %s to %s.',
      $this->renderAuthor(),
      $this->renderOldDate(),
      $this->renderNewDate());
  }

  public function getTitleForFeed() {
    return pht(
      '%s changed the end date for %s project %s to %s.',
      $this->renderAuthor(),
      $this->renderObject(),
      $this->renderOldDate(),
      $this->renderNewDate());
  }

  protected function getInvalidDateMessage() {
    return pht('End date is invalid.');
  }

}
