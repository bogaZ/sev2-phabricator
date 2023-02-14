<?php

final class CoursepathItemUnenrollTransaction
  extends CoursepathItemTransactionType {

  const TRANSACTIONTYPE = 'item.unenroll';

  public function generateOldValue($object) {
    return null;
  }

  public function applyExternalEffects($object, $value) {
    $enrollments = id(new CoursepathItemEnrollmentQuery())
      ->setViewer($this->getActor())
      ->withRegistrarPHIDs($value)
      ->withItemPHIDs(array($object->getPHID()))
      ->execute();
    $enrollments = mpull($enrollments, null, 'getRegistrarPHID');

    foreach ($value as $phid) {
      $enrollments[$phid]->delete();
    }
    return;
  }

  public function getTitle() {
    $new = $this->getNewValue();
    if (!is_array($new)) {
      $new = array();
    }
    $handles = $this->renderHandleList($new);
    return pht(
      '%s unenroll this course path from %s registrar(s): %s.',
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
      '%s unenrolled %s from %s registrar(s): %s.',
      $this->renderAuthor(),
      $this->renderObject(),
      new PhutilNumber(count($new)),
      $handles);
  }

  public function getIcon() {
    return 'fa-user-times';
  }

  public function validateTransactions($object, array $xactions) {
    $errors = array();

    foreach ($xactions as $xaction) {
      $enrollment_phids = $xaction->getNewValue();
      if (!$enrollment_phids) {
        $errors[] = $this->newRequiredError(
          pht('Registrar is required.'));
        continue;
      }

      foreach ($enrollment_phids as $enrollment_phid) {
        $enrollment = id(new CoursepathItemEnrollmentQuery())
          ->setViewer($this->getActor())
          ->withRegistrarPHIDs(array($enrollment_phid))
          ->withItemPHIDs(array($object->getPHID()))
          ->executeOne();
        if (!$enrollment) {
          $errors[] = $this->newInvalidError(
            pht(
              'Registrar PHID "%s" has not been enrolled.',
              $enrollment_phid));
        }
      }
    }

    return $errors;
  }

}
