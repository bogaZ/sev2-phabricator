<?php

final class LobbyStickitTransactionComment
  extends PhabricatorApplicationTransactionComment {

  public function getApplicationTransactionObject() {
    return new LobbyStickitTransaction();
  }

}
