<?php

final class SuiteBalanceEditor
  extends SuiteEditor {

  public function getEditorObjectsDescription() {
    return pht('Suite Balance');
  }

  public function getCreateObjectTitle($author, $object) {
    return pht('%s created this suite balance.', $author);
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
        case SuiteBalanceAmountTransaction::TRANSACTIONTYPE:
        case SuiteBalanceWithdrawableAmountTransaction::TRANSACTIONTYPE:
          return true;
      }
    }

    return false;
  }

  protected function applyInitialEffects(
    PhabricatorLiskDAO $object,
    array $xactions) {}


  protected function didApplyInternalEffects(
    PhabricatorLiskDAO $object,
    array $xactions) {
    foreach ($xactions as $key => $xaction) {}

    return $xactions;
  }


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
      SuiteBalanceTransaction::MAILTAG_CHANGE =>
        pht('Balance changed.'),
      SuiteProfileTransaction::MAILTAG_OTHER =>
        pht('Other balance activity not listed above occurs.'),
    );
  }

  protected function shouldPublishFeedStory(
    PhabricatorLiskDAO $object,
    array $xactions) {
    return false;
  }

  protected function buildReplyHandler(PhabricatorLiskDAO $object) {
    return id(new SuiteBalanceReplyHandler())
      ->setMailReceiver($object);
  }

  protected function buildMailTemplate(PhabricatorLiskDAO $object) {
    $phid = $object->getOwnerPHID();
    $id = $object->getID();
    $subject = pht('Balance %d: %s', $id, $phid);

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
      pht('Suite Balance Detail'),
      PhabricatorEnv::getProductionURI(
        '/suite/balance/view/'.$object->getID().'/'));
    return $body;
  }

  protected function getMailSubjectPrefix() {
    return pht('[Suite Balance]');
  }

}
