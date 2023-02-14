<?php

final class PhabricatorSuiteProjectsController
  extends PhabricatorSuiteController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {
    return id(new SuiteProjectSearchEngine())
      ->setViewer($this->getViewer())
      ->setController($this)
      ->buildResponse();

  }

  protected function requiresManageBilingCapability() {
    return true;
  }

  protected function requiresManageSubscriptionCapability() {
    return false;
  }

  protected function requiresManageUserCapability() {
    return false;
  }

}
