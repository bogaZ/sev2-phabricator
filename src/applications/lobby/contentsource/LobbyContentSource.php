<?php

final class LobbyContentSource
  extends PhabricatorContentSource {

  const SOURCECONST = 'lobby';

  public function getSourceName() {
    return pht('Lobby');
  }

  public function getSourceDescription() {
    return pht('Updates from lobby activities.');
  }

}
