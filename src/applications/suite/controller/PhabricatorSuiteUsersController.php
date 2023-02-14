<?php

final class PhabricatorSuiteUsersController
  extends PhabricatorSuiteController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {
    return id(new SuiteProfileSearchEngine())
      ->setViewer($this->getViewer())
      ->setController($this)
      ->buildResponse();

  }

  protected function requiresManageBilingCapability() {
    return false;
  }

  protected function requiresManageSubscriptionCapability() {
    return false;
  }

  protected function requiresManageUserCapability() {
    return true;
  }

}
