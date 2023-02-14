<?php

abstract class PhabricatorSuiteController
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
  abstract protected function requiresManageBilingCapability();
  abstract protected function requiresManageSubscriptionCapability();
  abstract protected function requiresManageUserCapability();


  protected function metRequiredCapabilities() {
    $can_manage = null;

    $app = id(new PhabricatorApplicationQuery())
      ->setViewer($this->getViewer())
      ->withClasses(array('PhabricatorSuiteApplication'))
      ->executeOne();
    if ($this->requiresManageUserCapability()) {
      $can_manage = PhabricatorPolicyFilter::hasCapability(
            $this->getViewer(),
            $app,
            PhabricatorSuiteCapabilityManageUser::CAPABILITY);

    }

    if ($this->requiresManageSubscriptionCapability()) {
      $can_manage = PhabricatorPolicyFilter::hasCapability(
            $this->getViewer(),
            $app,
            PhabricatorSuiteCapabilityManageSubscriptions::CAPABILITY);

    }

    if ($this->requiresManageBilingCapability()) {
      $can_manage = PhabricatorPolicyFilter::hasCapability(
            $this->getViewer(),
            $app,
            PhabricatorSuiteCapabilityManageBilling::CAPABILITY);

    }

    if (!$can_manage) {
      return new Aphront403Response();
    }
  }

  public function getPHID() {
    return null;
  }

}
