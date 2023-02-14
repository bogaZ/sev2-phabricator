<?php

abstract class LobbyController
  extends PhabricatorController {


  public function handleRequest(AphrontRequest $request) {
    $response = $this->metRequiredCapabilities();
    if ($response) {
     return $response;
    }

    return $this->afterMetRequiredCapabilities($request);
  }

  abstract protected function afterMetRequiredCapabilities(
    AphrontRequest $request);
  abstract protected function requiresManageCapability();
  abstract protected function requiresJoinCapability();

  protected function metRequiredCapabilities($strict = true) {
    $can_proceed = true;

    $app = id(new PhabricatorApplicationQuery())
      ->setViewer($this->getViewer())
      ->withClasses(array('PhabricatorLobbyApplication'))
      ->executeOne();

    if ($this->requiresJoinCapability()) {
      $can_proceed = PhabricatorPolicyFilter::hasCapability(
            $this->getViewer(),
            $app,
            LobbyJoinCapability::CAPABILITY);
    }

    if ($this->requiresManageCapability()) {
      $can_proceed = PhabricatorPolicyFilter::hasCapability(
            $this->getViewer(),
            $app,
            LobbyManageCapability::CAPABILITY);
    }

    if (!$can_proceed && $strict) {
      return new Aphront403Response();
    }

    if (!$strict) return $can_proceed;
  }

  public function getPHID() {
    return null;
  }

}
