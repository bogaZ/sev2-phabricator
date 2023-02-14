<?php

final class PhabricatorSuiteBalanceController
  extends PhabricatorSuiteController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {
    return id(new SuiteBalanceSearchEngine())
      ->setViewer($this->getViewer())
      ->setController($this)
      ->buildResponse();

  }

  protected function requiresManageBilingCapability() {
    return true;
  }

  protected function requiresManageSubscriptionCapability() {
    return true;
  }

  protected function requiresManageUserCapability() {
    return false;
  }

}
