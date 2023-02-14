<?php

final class LobbyStickitTransaction
  extends PhabricatorModularTransaction {

  public function getApplicationName() {
    return 'lobby';
  }

  public function getApplicationTransactionType() {
    return LobbyStickitPHIDType::TYPECONST;
  }

  public function getApplicationTransactionCommentObject() {
    return new LobbyStickitTransactionComment();
  }

  public function getBaseTransactionClass() {
    return 'LobbyStickitTransactionType';
  }
}
