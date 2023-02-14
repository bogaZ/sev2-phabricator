<?php

final class PerformanceWorklogController
  extends PerformanceController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {
    return id(new PerformanceWorklogSearchEngine())
      ->setViewer($this->getViewer())
      ->setController($this)
      ->buildResponse();
  }

  protected function buildApplicationCrumbs() {
    $paths_uri = $this->getApplicationURI('/worklog/');

    $crumbs = parent::buildApplicationCrumbs();
    $crumbs->addTextCrumb('Worklog', $paths_uri);
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
