<?php

final class LobbyReloadController
  extends LobbyController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {

    if (!$request->isAjax()) {
      // Kick the user home if they're not calling via ajax
      return id(new AphrontRedirectResponse())->setURI('/');
    }

    $lobby = id(new PHUILobbyView())
              ->setViewer($this->getViewer());

    return id(new AphrontAjaxResponse())->setContent(
      array(
        'main_pane' => implode('',
          $lobby->buildAllChannelsPanel($this->getViewer(), true)),
        'side_pane' => implode('', array(
          $lobby->buildLobbyPanel()->render(),
          $lobby->buildBreakPanel()->render(),
          $lobby->buildInactivePanel()->render(),
        )),
      ));
  }

  protected function requiresManageCapability() {
    return false;
  }

  protected function requiresJoinCapability() {
    return true;
  }
}
