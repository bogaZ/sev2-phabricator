<?php

final class LobbyStateTransactionQuery
  extends PhabricatorApplicationTransactionQuery {

  public function getTemplateApplicationTransaction() {
    return new LobbyStateTransaction();
  }

}
