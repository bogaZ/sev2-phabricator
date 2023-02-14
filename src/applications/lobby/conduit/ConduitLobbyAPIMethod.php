<?php

abstract class ConduitLobbyAPIMethod
  extends ConduitAPIMethod {

  final public function getApplication() {
    return PhabricatorApplication::getByClass(
      'PhabricatorLobbyApplication');
  }
}
