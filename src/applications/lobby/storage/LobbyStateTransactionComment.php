<?php

final class LobbyStateTransactionComment
  extends PhabricatorApplicationTransactionComment {

  public function getApplicationTransactionObject() {
    return new LobbyStateTransaction();
  }

}
