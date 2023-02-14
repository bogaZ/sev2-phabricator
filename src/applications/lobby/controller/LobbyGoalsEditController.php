<?php

final class LobbyGoalsEditController
  extends LobbyController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {
    $viewer = $this->getViewer();

    $engine = id(new LobbyGoalsEditEngine())
      ->setController($this)
      ->addContextParameter('responseType');

    return $engine->buildResponse();
  }

  protected function buildApplicationCrumbs() {
    $paths_uri = $this->getApplicationURI('/goals');

    $crumbs = parent::buildApplicationCrumbs();
    $crumbs->addTextCrumb('Goals', $paths_uri);
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
