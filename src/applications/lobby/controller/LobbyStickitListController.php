<?php

final class LobbyStickitListController
  extends LobbyController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {
    return id(new LobbyStickitSearchEngine())
      ->setController($this)
      ->buildResponse();
  }

  protected function buildApplicationCrumbs() {
    $paths_uri = $this->getApplicationURI('/stickit');

    $crumbs = parent::buildApplicationCrumbs();
    $crumbs->addTextCrumb('Stick-It', $paths_uri);
    $crumbs->setBorder(true);

    id(new LobbyStickitEditEngine())
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
