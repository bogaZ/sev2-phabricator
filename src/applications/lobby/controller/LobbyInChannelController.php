<?php

final class LobbyInChannelController
  extends LobbyController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {

    if (!$request->isAjax()) {
      // Kick the user home if they're not calling via ajax
      return id(new AphrontRedirectResponse())->setURI('/');
    }

    $viewer = $request->getViewer();
    $current_channel = $request->getURIData('phid');

    $lobby = id(new PHUILobbyView())
              ->setViewer($viewer);

    $states = id(new LobbyStateQuery())
              ->setViewer(PhabricatorUser::getOmnipotentUser())
              ->withStatus(LobbyState::STATUS_IN_CHANNEL)
              ->withCurrentChannel($current_channel)
              ->needOwner(true)
              ->execute();

    return id(new AphrontAjaxResponse())->setContent(
      array(
        'members_pane' => Lobby::buildInChannelBadges($states,
          $current_channel)->render(),
        'threadPHID' => $current_channel,
        'count' => count($states),
        'members' => array_filter(array_values(mpull($states, 'getOwnerPHID')))
      ));
  }

  protected function requiresManageCapability() {
    return false;
  }

  protected function requiresJoinCapability() {
    return true;
  }
}
