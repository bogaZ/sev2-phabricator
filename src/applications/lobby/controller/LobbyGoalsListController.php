<?php

final class LobbyGoalsListController
  extends LobbyController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {
    return id(new LobbyGoalsSearchEngine())
      ->setController($this)
      ->buildResponse();
  }

  protected function buildApplicationCrumbs() {
    $paths_uri = $this->getApplicationURI('/goals');

    $crumbs = parent::buildApplicationCrumbs();
    $crumbs->addTextCrumb('Goals', $paths_uri);
    $crumbs->setBorder(true);

    id(new LobbyGoalsEditEngine())
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
