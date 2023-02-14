<?php

final class LobbyJoinController
  extends LobbyController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {

    if (!$request->isAjax()) {
      // Kick the user home if they're not calling via ajax
      return id(new AphrontRedirectResponse())->setURI('/');
    }

    $user = $request->getUser();
    $content_source = PhabricatorContentSource::newFromRequest($request);

    $joined = false;
    $lobby = null;
    $error = null;
    try {
      $lobby = id(new Lobby())
                ->setViewer($user)
                ->joinLobby(
        $user, $content_source,
        $request->getStr('device', 'phone'));

      LobbyAphlict::broadcastLobby();
      $joined = true;
    } catch (Exception $ex) {
      $error = $ex;
    } catch (Throwable $e) {
      $error = $e;
    }

    return id(new AphrontAjaxResponse())->setContent(
      array(
        'joined' => $joined,
        'phid' => $lobby ? $lobby->getPHID() : null,
        'error' => $error ? $error->getMessage() : null,
      ));
  }

  protected function requiresManageCapability() {
    return false;
  }

  protected function requiresJoinCapability() {
    return true;
  }
}
