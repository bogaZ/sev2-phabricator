<?php

final class JobPostingInviteTransaction
  extends JobPostingTransactionType {

  const TRANSACTIONTYPE = 'job.invite';

  public function generateOldValue($object) {
    return null;
  }

  public function applyExternalEffects($object, $value) {
    foreach ($value as $phid) {
      $enrollment = JobPostingApplicant::initializeNewApplicant(
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
      '%s invited %s new applicant(s): %s.',
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
      '%s invited %s new applicant(s): %s.',
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
          pht('Invitee is required.'));
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
              'Invitee PHID "%s" is not a valid user PHID.',
              $user_phid));
          continue;
        }

        // Check if already enrollmented
        $applicants = id(new JobInviteQuery())
          ->setViewer($this->getActor())
          ->withApplicantPHIDs(array($user_phid))
          ->withPostingPHIDs(array($object->getPHID()))
          ->executeOne();
        if ($applicants) {
          $errors[] = $this->newInvalidError(
            pht(
              '%s has already been invited this job posting.',
              $user->getUsername()));
        }
      }
    }

    return $errors;
  }

}
