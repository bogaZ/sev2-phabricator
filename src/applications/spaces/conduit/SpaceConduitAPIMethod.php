<?php

abstract class SpaceConduitAPIMethod extends ConduitAPIMethod {

  final public function getApplication() {
    return PhabricatorApplication::getByClass(
      'PhabricatorSpacesApplication');
  }
}
