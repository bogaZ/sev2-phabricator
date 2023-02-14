<?php

final class LobbyStateEditor
  extends PhabricatorApplicationTransactionEditor {

  public function getEditorApplicationClass() {
    return 'PhabricatorLobbyApplication';
  }

   protected function getMailSubjectPrefix() {
    return '[Lobby]';
  }

  public function getEditorObjectsDescription() {
    return pht('State');
  }

  public function getCreateObjectTitle($author, $object) {
    return pht('%s has been created.', $object);
  }

  public function getCreateObjectTitleForFeed($author, $object) {
    return pht('%s has been created.', $object);
  }

  protected function supportsSearch() {
    return false;
  }

  protected function shouldApplyInitialEffects(
    PhabricatorLiskDAO $object,
    array $xactions) {

    foreach ($xactions as $xaction) {
      switch ($xaction->getTransactionType()) {
        case LobbyStateStatusTransaction::TRANSACTIONTYPE:
        case LobbyStateIsWorkingTransaction::TRANSACTIONTYPE:
        case LobbyStateDeviceTransaction::TRANSACTIONTYPE:
        case LobbyStateCurrentChannelTransaction::TRANSACTIONTYPE:
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
    return false;
  }

  public function getMailTagsMap() {
    return array();
  }

  protected function shouldPublishFeedStory(
    PhabricatorLiskDAO $object,
    array $xactions) {
    return false;
  }
}
