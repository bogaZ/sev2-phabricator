<?php

final class PhabricatorJobPostingEditor
  extends PhabricatorApplicationTransactionEditor {

  public function getEditorApplicationClass() {
    return 'PhabricatorJobApplication';
  }

  public function getEditorObjectsDescription() {
    return pht('Job posting');
  }

  public function getCreateObjectTitle($author, $object) {
    return pht('%s created this job posting.', $author);
  }

  public function getCreateObjectTitleForFeed($author, $object) {
    return pht('%s created %s.', $author, $object);
  }

  protected function supportsSearch() {
    return true;
  }

  public function getTransactionTypes() {
    $types = parent::getTransactionTypes();
    $types[] = PhabricatorTransactions::TYPE_COMMENT;
    $types[] = PhabricatorTransactions::TYPE_EDGE;
    $types[] = PhabricatorTransactions::TYPE_EDIT_POLICY;

    return $types;
  }

  protected function shouldSendMail(
    PhabricatorLiskDAO $object,
    array $xactions) {
    return true;
  }

  public function getMailTagsMap() {
    return array(
      JobPostingTransaction::MAILTAG_DETAILS =>
        pht('Someone changes the job\'s details.'),
      JobPostingTransaction::MAILTAG_COMMENT =>
        pht('Someone comments on a job.'),
      JobPostingTransaction::MAILTAG_OTHER =>
        pht('Other job activity not listed above occurs.'),
    );
  }

  protected function shouldPublishFeedStory(
    PhabricatorLiskDAO $object,
    array $xactions) {
    return true;
  }

  protected function expandTransactions(
    PhabricatorLiskDAO $object,
    array $xactions) {

    $actor = $this->getActor();
    $actor_phid = $actor->getPHID();

    $results = parent::expandTransactions($object, $xactions);

    // Automatically subscribe the author when they create a course path.
    if ($this->getIsNewObject()) {
      if ($actor_phid) {
        $results[] = id(new JobPostingTransaction())
          ->setTransactionType(PhabricatorTransactions::TYPE_SUBSCRIBERS)
          ->setNewValue(
            array(
              '+' => array($actor_phid => $actor_phid),
            ));
      }
    }

    return $results;
  }

  protected function buildReplyHandler(PhabricatorLiskDAO $object) {
    return id(new PhabricatorJobReplyHandler())
      ->setMailReceiver($object);
  }

  protected function buildMailTemplate(PhabricatorLiskDAO $object) {
    $name = $object->getName();
    $id = $object->getID();
    $subject = pht('Course Path %d: %s', $id, $name);

    return id(new PhabricatorMetaMTAMail())
      ->setSubject($subject);
  }

  protected function getMailTo(PhabricatorLiskDAO $object) {
    return array(
      $object->getCreatorPHID(),
      $this->requireActor()->getPHID(),
    );
  }

  protected function buildMailBody(
    PhabricatorLiskDAO $object,
    array $xactions) {

    $body = parent::buildMailBody($object, $xactions);

    $body->addLinkSection(
      pht('JOB POSTING DETAIL'),
      PhabricatorEnv::getProductionURI('/view/'.$object->getID().'/'));
    return $body;
  }

  protected function getMailSubjectPrefix() {
    return pht('[Job]');
  }

  protected function applyFinalEffects(
    PhabricatorLiskDAO $object,
    array $xactions) {

    $posting_phid = $object->getPHID();
    $user_phids = array();
    $clear_everything = false;

    foreach ($xactions as $xaction) {
      switch ($xaction->getTransactionType()) {
        case JobPostingInviteTransaction::TRANSACTIONTYPE:
        case JobPostingUninviteTransaction::TRANSACTIONTYPE:
          foreach ($xaction->getNewValue() as $user_phid) {
            $user_phids[] = $user_phid;
          }
          break;
        default:
          $clear_everything = true;
          break;
      }
    }

    if ($clear_everything) {
      $applicants = id(new JobInviteQuery())
        ->setViewer($this->getActor())
        ->withPostingPHIDs(array($posting_phid))
        ->execute();
      $user_phids[] = mpull($applicants, null, 'getApplicantPHID');
    }

    return $xactions;
  }

}
