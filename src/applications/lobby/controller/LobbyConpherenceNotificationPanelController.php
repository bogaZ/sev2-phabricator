<?php

final class LobbyConpherenceNotificationPanelController
  extends LobbyController {

  public function handleRequest(AphrontRequest $request) {
    $ok = $this->metRequiredCapabilities(false);
    if (!$ok || $request->isMobileDevice()) {
      // If current user couldn't join lobby
      // or it was a request to gather old message,
      // delegate back to conpherence
      return $this->delegateToConpherence();
    }

    return $this->afterMetRequiredCapabilities($request);
  }

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {
    $user = $request->getUser();
    $conpherences = array();
    require_celerity_resource('conpherence-notification-css');

    $participant_data = id(new ConpherenceParticipantThreadUnreadQuery())
      ->withParticipantPHIDs(array($user->getPHID()))
      ->execute();

    if ($participant_data) {
      $conpherences = id(new ConpherenceThreadQuery())
        ->setViewer($user)
        ->withPHIDs($participant_data)
        ->needProfileImage(true)
        ->needTransactions(true)
        ->setTransactionLimit(100)
        ->execute();
    }

    if ($conpherences) {
      // re-order the conpherences based on participation data
      $view = new AphrontNullView();
      foreach ($conpherences as $conpherence) {
        $d_data = $conpherence->getDisplayData($user);
        $classes = array(
          'phabricator-notification',
          'conpherence-notification',
          'phabricator-notification-unread'
        );

        $uri = '/lobby/inline-reply/'.$conpherence->getID().'/';
        $title = $d_data['title'];
        $subtitle = $d_data['subtitle'];
        $unread_count = $d_data['unread_count'];
        $epoch = $d_data['epoch'];
        $image = $d_data['image'];

        $msg_view = id(new LobbyMenuItemView())
          ->setUser($user)
          ->setTitle($title)
          ->setSubtitle($subtitle)
          ->setHref($uri)
          ->setEpoch($epoch)
          ->setImageURI($image)
          ->setUnreadCount($unread_count);

        $view->appendChild(javelin_tag(
          'div',
          array(
            'class' => implode(' ', $classes),
            'sigil' => 'notification',
            'meta' => array(
              'href' => $uri,
            ),
          ),
          $msg_view));
      }
      $content = $view->render();
    } else {
      $rooms_uri = phutil_tag(
        'a',
        array(
          'href' => '/conpherence/search/',
          'class' => 'no-room-notification',
        ),
        pht('You have no unread messages.'));

      $content = phutil_tag_div(
        'phabricator-notification no-notifications', $rooms_uri);
    }

    $content = hsprintf(
      '<div class="phabricator-notification-header grouped">%s</div>'.
      '%s',
      phutil_tag(
        'a',
        array(
          'href' => '/conpherence/search/',
        ),
        pht('Rooms')),
      $content);

    $unread = id(new ConpherenceParticipantCountQuery())
      ->withParticipantPHIDs(array($user->getPHID()))
      ->withUnread(true)
      ->execute();
    $unread_count = idx($unread, $user->getPHID(), 0);

    $json = array(
      'content' => $content,
      'number'  => (int)$unread_count,
    );

    return id(new AphrontAjaxResponse())->setContent($json);
  }

  protected function requiresManageCapability() {
    return false;
  }

  protected function requiresJoinCapability() {
    return true;
  }

  protected function delegateToConpherence() {
    $request = $this->getRequest();

    $controller = id(new ConpherenceNotificationPanelController());
    $controller->setDelegatingController($this);
    $controller->setRequest($request);

    $application = $this->getCurrentApplication();
    if ($application) {
      $controller->setCurrentApplication($application);
    }

    return $controller->handleRequest($request);
  }
}
