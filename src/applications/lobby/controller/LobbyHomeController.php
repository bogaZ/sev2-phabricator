<?php

final class LobbyHomeController
  extends LobbyController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {
    return id(new LobbyStateSearchEngine())
      ->setController($this)
      ->buildResponse();
  }

  protected function requiresManageCapability() {
    return false;
  }

  protected function requiresJoinCapability() {
    return true;
  }
}
