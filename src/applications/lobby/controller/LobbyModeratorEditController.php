<?php

final class LobbyModeratorEditController
  extends LobbyController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {
    $viewer = $this->getViewer();

    $engine = id(new LobbyModeratorEditEngine())
      ->setController($this);

    return $engine->buildResponse();
  }

  protected function buildApplicationCrumbs() {
    $paths_uri = $this->getApplicationURI('/moderators');

    $crumbs = parent::buildApplicationCrumbs();
    $crumbs->addTextCrumb('Moderators', $paths_uri);
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
