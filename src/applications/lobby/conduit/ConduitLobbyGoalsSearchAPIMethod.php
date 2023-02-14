<?php

final class ConduitLobbyGoalsSearchAPIMethod extends
PhabricatorSearchEngineAPIMethod {

  public function getAPIMethodName() {
    return 'lobby.goals.search';
  }

  public function newSearchEngine() {
    return new LobbyGoalsSearchEngine();
  }

  public function getMethodSummary() {
    return pht('Read information about goals.');
  }
}
