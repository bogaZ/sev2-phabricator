<?php

final class ConduitLobbyStickitSearchAPIMethod extends
PhabricatorSearchEngineAPIMethod {

  public function getAPIMethodName() {
    return 'lobby.stickit.search';
  }

  public function newSearchEngine() {
    return new LobbyStickitSearchEngine();
  }

  public function getMethodSummary() {
    return pht('Read information about stickit.');
  }
}
