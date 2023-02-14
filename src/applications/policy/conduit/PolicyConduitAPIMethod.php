<?php

abstract class PolicyConduitAPIMethod extends ConduitAPIMethod {

  final public function getApplication() {
    return PhabricatorApplication::getByClass(
      'PhabricatorPolicyApplication');
  }
}
