<?php

final class LobbyModeratorTransaction
  extends PhabricatorModularTransaction {

  public function getTableName() {
    return sev2table('moderatorstransaction');
  }

  public function getApplicationName() {
    return 'lobby';
  }

  public function getApplicationTransactionType() {
    return LobbyModeratorPHIDType::TYPECONST;
  }

  public function getBaseTransactionClass() {
    return 'LobbyModeratorTransactionType';
  }
}
