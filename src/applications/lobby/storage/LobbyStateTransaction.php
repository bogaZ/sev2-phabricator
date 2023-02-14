<?php

final class LobbyStateTransaction
  extends PhabricatorModularTransaction {

  public function getApplicationName() {
    return 'lobby';
  }

  public function getApplicationTransactionType() {
    return LobbyStatePHIDType::TYPECONST;
  }

  public function getApplicationTransactionCommentObject() {
    return new LobbyStateTransactionComment();
  }

  public function getBaseTransactionClass() {
    return 'LobbyStateTransactionType';
  }
}
