<?php

final class SuiteProfileEditor
  extends SuiteEditor {

  public function getEditorObjectsDescription() {
    return pht('Suite Profile');
  }

  public function getCreateObjectTitle($author, $object) {
    return pht('%s created this suite profile.', $author);
  }

  public function getCreateObjectTitleForFeed($author, $object) {
    return pht('%s created %s.', $author, $object);
  }

  protected function supportsSearch() {
    return true;
  }

  protected function shouldApplyInitialEffects(
    PhabricatorLiskDAO $object,
    array $xactions) {

    foreach ($xactions as $xaction) {
      switch ($xaction->getTransactionType()) {
        case SuiteProfileGraduationTargetMonthTransaction::TRANSACTIONTYPE:
        case SuiteProfileUpForTransaction::TRANSACTIONTYPE:
        case SuiteProfileIsRspTransaction::TRANSACTIONTYPE:
          return true;
      }
    }

    return false;
  }

  protected function applyInitialEffects(
    PhabricatorLiskDAO $object,
    array $xactions) {}

  public function getTransactionTypes() {
    $types = parent::getTransactionTypes();
    $types[] = PhabricatorTransactions::TYPE_EDGE;
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
      SuiteProfileTransaction::MAILTAG_DETAILS =>
        pht('Someone changes the profile\'s details.'),
      SuiteProfileTransaction::MAILTAG_OTHER =>
        pht('Other profile activity not listed above occurs.'),
    );
  }

  protected function shouldPublishFeedStory(
    PhabricatorLiskDAO $object,
    array $xactions) {
    return false;
  }

  protected function buildReplyHandler(PhabricatorLiskDAO $object) {
    return id(new SuiteProfileReplyHandler())
      ->setMailReceiver($object);
  }

  protected function buildMailTemplate(PhabricatorLiskDAO $object) {
    $phid = $object->getOwnerPHID();
    $id = $object->getID();
    $subject = pht('Suite Profile %d: %s', $id, $phid);

    return id(new PhabricatorMetaMTAMail())
      ->setSubject($subject);
  }

  protected function getMailTo(PhabricatorLiskDAO $object) {
    return array(
      $object->getOwnerPHID(),
    );
  }

  protected function buildMailBody(
    PhabricatorLiskDAO $object,
    array $xactions) {

    $body = parent::buildMailBody($object, $xactions);

    $body->addLinkSection(
      pht('Suite Profile Detail'),
      PhabricatorEnv::getProductionURI(
        '/suite/users/view/'.$object->getID().'/'));
    return $body;
  }

  protected function getMailSubjectPrefix() {
    return pht('[Suite Profile]');
  }


}
