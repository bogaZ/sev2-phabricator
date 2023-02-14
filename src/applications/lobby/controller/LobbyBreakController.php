<?php

final class LobbyBreakController
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
    $phid = $request->getURIData('phid');
    $const = $request->getURIData('const');

    $content_source = PhabricatorContentSource::newFromRequest($request);

    $lobby = id(new LobbyStateQuery())
              ->setViewer($user)
              ->withPHIDs(array($phid))
              ->executeOne();

    if (!$lobby) {
      return new Aphront404Response();
    }

    if ($request->isFormPost()) {
      try {
        $lobby = id(new Lobby())
                  ->setViewer($user)
                  ->joinLobby(
          $user, $content_source,
          $const,
          $lobby->getDevice(), true);

      } catch (Exception $ex) {
        $error = $ex;
      } catch (Throwable $e) {
        $error = $e;
      }

      return id(new AphrontRedirectResponse())->setURI('/');
    } else {
      try {
        $lobby = id(new Lobby())
                  ->setViewer($user)
                  ->changeStatus(
          $user, $content_source,
          $const);

      } catch (Exception $ex) {
        $error = $ex;
      } catch (Throwable $e) {
        $error = $e;
      }
    }

    LobbyAphlict::broadcastLobby();

    $statuses = LobbyState::getStatusMap();

    return $this->newDialog()
      ->setTitle(pht('Break for %s', $statuses[$const]))
      ->setShortTitle(pht('Break for %s',$statuses[$const]))
      ->appendParagraph('You are in a break, you can leave this window open'.
        ' until you are back.')
      ->addSubmitButton('I\'m Back');
  }

  protected function requiresManageCapability() {
    return false;
  }

  protected function requiresJoinCapability() {
    return true;
  }
}
