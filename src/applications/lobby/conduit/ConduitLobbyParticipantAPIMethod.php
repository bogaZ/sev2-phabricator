<?php

final class ConduitLobbyParticipantAPIMethod extends
  PhabricatorSearchEngineAPIMethod {

  public function getAPIMethodName() {
    return 'lobby.participant.list';
  }

  public function newSearchEngine() {
    return new LobbyStateSearchEngine();
  }

  public function getMethodSummary() {
    return pht('Lobby Participant List.');
  }

}
