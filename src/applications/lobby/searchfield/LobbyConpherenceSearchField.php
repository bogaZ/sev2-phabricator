<?php

final class LobbyConpherenceSearchField
  extends PhabricatorSearchTokenizerField {

  protected function getDefaultValue() {
    return array();
  }

  protected function newDatasource() {
    return new LobbyConpherenceFunctionDatasource();
  }

  protected function newConduitParameterType() {
    return new ConduitLobbyConpherenceListParameterType();
  }

}
