<?php

final class LobbyStickitEditController
  extends LobbyController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {
    $viewer = $this->getViewer();

    $engine = id(new LobbyStickitEditEngine())
    ->setController($this)
    ->addContextParameter('responseType')
    ->buildResponse();
    return $engine;
  }

  protected function buildApplicationCrumbs() {
    $paths_uri = $this->getApplicationURI('/stickit');

    $crumbs = parent::buildApplicationCrumbs();
    $crumbs->addTextCrumb('Stick-It', $paths_uri);
    $crumbs->setBorder(true);
    return $crumbs;
  }

  protected function requiresManageCapability() {
    return true;
  }

  protected function requiresJoinCapability() {
    return true;
  }
}
