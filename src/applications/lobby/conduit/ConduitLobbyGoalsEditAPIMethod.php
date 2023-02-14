<?php

final class ConduitLobbyGoalsEditAPIMethod
  extends PhabricatorEditEngineAPIMethod {

  public function getAPIMethodName() {
    return 'lobby.goals.edit';
  }

  public function newEditEngine() {
    return new LobbyGoalsEditEngine();
  }

  public function getMethodSummary() {
    return pht(
      'Apply transactions to create a new goals or edit an existing one.');
  }

}
