<?php

final class ConduitLobbyStickitEditAPIMethod
  extends PhabricatorEditEngineAPIMethod {

  public function getAPIMethodName() {
    return 'lobby.stickit.edit';
  }

  public function newEditEngine() {
    return new LobbyStickitEditEngine();
  }

  public function getMethodSummary() {
    return pht(
      'Apply transactions to create a new stickit or edit an existing one.');
  }

}
