<?php

final class PerformancePipController
  extends PerformanceController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {
    return id(new PerformancePipSearchEngine())
      ->setViewer($this->getViewer())
      ->setController($this)
      ->buildResponse();

  }

  protected function buildApplicationCrumbs() {
    $paths_uri = $this->getApplicationURI("/pip/");

    $crumbs = parent::buildApplicationCrumbs();
    $crumbs->addTextCrumb("Improvement Period", $paths_uri);
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
