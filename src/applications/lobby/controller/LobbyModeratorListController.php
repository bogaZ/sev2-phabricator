<?php

final class LobbyModeratorListController
  extends LobbyController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {
    return id(new LobbyModeratorSearchEngine())
      ->setController($this)
      ->buildResponse();
  }

  protected function buildApplicationCrumbs() {
    $paths_uri = $this->getApplicationURI('/moderators');

    $crumbs = parent::buildApplicationCrumbs();
    $crumbs->addTextCrumb('Moderators', $paths_uri);
    $crumbs->setBorder(true);

    id(new LobbyModeratorEditEngine())
      ->setViewer($this->getViewer())
      ->addActionToCrumbs($crumbs);

    return $crumbs;
  }

  protected function requiresManageCapability() {
    return true;
  }

  protected function requiresJoinCapability() {
    return true;
  }
}
