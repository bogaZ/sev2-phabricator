<?php

final class LobbyModeratorDisableController
  extends LobbyController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {
    $viewer = $this->getViewer();
    $id = $request->getURIData('id');
    $via = $request->getURIData('via');

    $moderator = id(new LobbyModeratorQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->needModerator(true)
      ->needChannel(true)
      ->executeOne();
    if (!$moderator) {
      return new Aphront404Response();
    }

    $actor = $viewer;
    $done_uri = $this->getApplicationURI('moderators');
    $should_disable = true;

    if ($viewer->getPHID() == $moderator->getModerator()->getPHID()) {
      return $this->newDialog()
        ->setTitle(pht('Something Stays Your Hand'))
        ->appendParagraph(
          pht(
            'Try as you might, you find you can not disable your own account.'))
        ->addCancelButton($done_uri, pht('Curses!'));
    }

    if ($request->isFormPost()) {
      $moderator->delete();

      return id(new AphrontRedirectResponse())->setURI($done_uri);
    }

    $title = pht('Disable Moderator?');
    $short_title = pht('Disable Moderator');

    $body = pht(
      'Disable %s? They will no longer be able to moderate %s.',
      phutil_tag('strong', array(), $moderator->getModerator()->getUsername()),
      phutil_tag('strong', array(), '#'.$moderator->getChannel()->getTitle()));

    $submit = pht('Disable');

    return $this->newDialog()
      ->setTitle($title)
      ->setShortTitle($short_title)
      ->appendParagraph($body)
      ->addCancelButton($done_uri)
      ->addSubmitButton($submit);
  }

  protected function requiresManageCapability() {
    return true;
  }

  protected function requiresJoinCapability() {
    return true;
  }
}
