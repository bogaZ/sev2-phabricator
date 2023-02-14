<?php

final class PerformanceProgressController
  extends PerformanceController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {
    return id(new PerformanceProgressSearchEngine())
      ->setViewer($this->getViewer())
      ->setController($this)
      ->buildResponse();
  }

  protected function buildApplicationCrumbs() {
    $paths_uri = $this->getApplicationURI('/progress/');

    $crumbs = parent::buildApplicationCrumbs();
    $crumbs->addTextCrumb('Progress', $paths_uri);
    $crumbs->setBorder(true);
    return $crumbs;
  }

  protected function requiresManageCapability() {
    return false;
  }

  protected function requiresViewCapability() {
    return true;
  }
}
