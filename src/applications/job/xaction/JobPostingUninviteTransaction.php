<?php

final class JobPostingUninviteTransaction
  extends JobPostingTransactionType {

  const TRANSACTIONTYPE = 'job.uninvite';

  public function generateOldValue($object) {
    return null;
  }

  public function applyExternalEffects($object, $value) {
    $applicants = id(new JobInviteQuery())
      ->setViewer($this->getActor())
      ->withApplicantPHIDs($value)
      ->withPostingPHIDs(array($object->getPHID()))
      ->execute();
    $applicants = mpull($applicants, null, 'getApplicantPHID');

    foreach ($value as $phid) {
      $applicants[$phid]->delete();
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
      '%s uninvited %s applicant(s): %s.',
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
      '%s uninvited %s applicant(s): %s.',
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
      $applicant_phids = $xaction->getNewValue();
      if (!$applicant_phids) {
        $errors[] = $this->newRequiredError(
          pht('Invitee is required.'));
        continue;
      }

      foreach ($applicant_phids as $applicant_phid) {
        $invited = id(new JobInviteQuery())
          ->setViewer($this->getActor())
          ->withApplicantPHIDs(array($applicant_phid))
          ->withPostingPHIDs(array($object->getPHID()))
          ->executeOne();
        if (!$invited) {
          $errors[] = $this->newInvalidError(
            pht(
              'Applicant "%s" has not been invited.',
              $applicant_phid));
        }
      }
    }

    return $errors;
  }
}
