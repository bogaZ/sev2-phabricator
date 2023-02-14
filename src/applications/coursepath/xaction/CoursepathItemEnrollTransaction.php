<?php

final class CoursepathItemEnrollTransaction
  extends CoursepathItemTransactionType {

  const TRANSACTIONTYPE = 'item.enroll';

  public function generateOldValue($object) {
    return null;
  }

  public function applyExternalEffects($object, $value) {
    foreach ($value as $phid) {
      $enrollment = CoursepathItemEnrollment::initializeNewEnrollment(
        $this->getActor(),
        $object,
        $phid);
      $enrollment->save();
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
      '%s enrolled this course path to %s registrar(s): %s.',
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
      '%s enrollmented %s to %s registrar(s): %s.',
      $this->renderAuthor(),
      $this->renderObject(),
      new PhutilNumber(count($new)),
      $handles);
  }

  public function getIcon() {
    return 'fa-user-plus';
  }

  public function validateTransactions($object, array $xactions) {
    $errors = array();

    foreach ($xactions as $xaction) {
      $user_phids = $xaction->getNewValue();
      if (!$user_phids) {
        $errors[] = $this->newRequiredError(
          pht('Registrar is required.'));
        continue;
      }

      foreach ($user_phids as $user_phid) {
        // Check if a valid user
        $user = id(new PhabricatorPeopleQuery())
          ->setViewer($this->getActor())
          ->withPHIDs(array($user_phid))
          ->executeOne();
        if (!$user) {
          $errors[] = $this->newInvalidError(
            pht(
              'Registrar PHID "%s" is not a valid user PHID.',
              $user_phid));
          continue;
        }

        // Check if already enrollmented
        $enrollment = id(new CoursepathItemEnrollmentQuery())
          ->setViewer($this->getActor())
          ->withRegistrarPHIDs(array($user_phid))
          ->withItemPHIDs(array($object->getPHID()))
          ->executeOne();
        if ($enrollment) {
          $errors[] = $this->newInvalidError(
            pht(
              '%s has already been enrolled this course path.',
              $user->getUsername()));
        }
      }
    }

    return $errors;
  }

}
