<?php

final class PhabricatorSuiteUsersEditController
  extends PhabricatorSuiteController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {
    exit('here');
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
