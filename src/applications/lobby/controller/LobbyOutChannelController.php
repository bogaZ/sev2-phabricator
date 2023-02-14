<?php

final class LobbyOutChannelController
  extends LobbyController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {

    $viewer = $request->getViewer();
    $viewer->loadUserProfile();
    $current_channel = $request->getURIData('phid');

    LobbyAphlict::broadcastLeavingChannel($current_channel,
      $viewer->getPHID(), array(
        'name' => $viewer->getFullName(),
        'image_uri' => $viewer->getProfileImageURI(),
      ));

    $content_source = PhabricatorContentSource::newFromRequest($request);
    $leaved = false;
    try {
      $lobby = id(new Lobby())
                ->setViewer($viewer)
                ->changeStatus(
        $viewer, $content_source,
        LobbyState::STATUS_BREAK_OTHER);

      $leaved = true;
    } catch (Exception $ex) {
      $error = $ex;
    } catch (Throwable $e) {
      $error = $e;
    }

    return id(new AphrontAjaxResponse())->setContent(
      array(
        'leaved' => $leaved
      ));
  }

  protected function requiresManageCapability() {
    return false;
  }

  protected function requiresJoinCapability() {
    return true;
  }
}
