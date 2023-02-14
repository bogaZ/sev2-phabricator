<?php

final class PerformanceViewController
  extends PerformanceController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {
    return id(new PerformanceSearchEngine())
      ->setViewer($this->getViewer())
      ->setController($this)
      ->buildResponse();

  }

  protected function requiresManageCapability() {
    return true;
  }

  protected function requiresViewCapability() {
    return true;
  }
}
