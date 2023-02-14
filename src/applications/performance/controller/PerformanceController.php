<?php

abstract class PerformanceController
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
  abstract protected function requiresViewCapability();

  protected function metRequiredCapabilities() {
    $can_manage = true;

    $app = id(new PhabricatorApplicationQuery())
      ->setViewer($this->getViewer())
      ->withClasses(array('PhabricatorPerformanceApplication'))
      ->executeOne();

    if ($this->requiresViewCapability()) {
      $can_manage = PhabricatorPolicyFilter::hasCapability(
            $this->getViewer(),
            $app,
            PhabricatorPolicyCapability::CAN_VIEW);
    }

    if ($this->requiresManageCapability()) {
      $can_manage = PhabricatorPolicyFilter::hasCapability(
            $this->getViewer(),
            $app,
            PerformanceManageCapability::CAPABILITY);
    }

    if (!$can_manage) {
      return new Aphront403Response();
    }
  }

  public function getPHID() {
    return null;
  }

}
