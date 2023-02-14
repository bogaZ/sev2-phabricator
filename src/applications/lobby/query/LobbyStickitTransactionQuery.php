<?php

final class LobbyStickitTransactionQuery
  extends PhabricatorApplicationTransactionQuery {

  public function getTemplateApplicationTransaction() {
    return new LobbyStickitTransaction();
  }

}
