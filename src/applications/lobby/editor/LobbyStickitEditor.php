<?php

final class LobbyStickitEditor
  extends PhabricatorApplicationTransactionEditor {

  public function getEditorApplicationClass() {
    return 'PhabricatorLobbyApplication';
  }

   protected function getMailSubjectPrefix() {
    return '[Lobby]';
  }

  public function getEditorObjectsDescription() {
    return pht('Stickit');
  }

  public function getCreateObjectTitle($author, $object) {
    return pht('%s has been created.', $object);
  }

  public function getCreateObjectTitleForFeed($author, $object) {
    return pht('%s has been created.', $object);
  }

  public function getTransactionTypes() {
    $types = parent::getTransactionTypes();
    $types[] = PhabricatorTransactions::TYPE_EDGE;
    return $types;
  }

  protected function supportsSearch() {
    return false;
  }

  protected function shouldApplyInitialEffects(
    PhabricatorLiskDAO $object,
    array $xactions) {

    foreach ($xactions as $xaction) {
      switch ($xaction->getTransactionType()) {
        case LobbyStickitTitleTransaction::TRANSACTIONTYPE:
        case LobbyStickitNoteTypeTransaction::TRANSACTIONTYPE:
        case LobbyStickitContentTransaction::TRANSACTIONTYPE:
          return true;
      }
    }

    return false;
  }

  protected function applyInitialEffects(
    PhabricatorLiskDAO $object,
    array $xactions) {}


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
