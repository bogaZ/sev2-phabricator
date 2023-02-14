<?php

final class ConpherenceNotificationPanelController
  extends ConpherenceController {

  public function handleRequest(AphrontRequest $request) {
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

        $uri = $this->getApplicationURI($conpherence->getID().'/');
        $title = $d_data['title'];
        $subtitle = $d_data['subtitle'];
        $unread_count = $d_data['unread_count'];
        $epoch = $d_data['epoch'];
        $image = $d_data['image'];

        $msg_view = id(new ConpherenceMenuItemView())
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
          'href' => '/conpherence/',
          'class' => 'no-room-notification',
        ),
        pht('You have no unread messages.'));

      $content = phutil_tag_div(
        'phabricator-notification no-notifications', $rooms_uri);
    }

    $content = hsprintf(
      '<div class="phabricator-notification-header grouped">%s%s</div>'.
      '%s',
      phutil_tag(
        'a',
        array(
          'href' => '/conpherence/',
        ),
        pht('Rooms')),
      $this->renderPersistentOption(),
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

  private function renderPersistentOption() {
    $viewer = $this->getViewer();
    $column_key = PhabricatorConpherenceColumnVisibleSetting::SETTINGKEY;
    $show = (bool)$viewer->getUserSetting($column_key, false);

    $view = phutil_tag(
      'div',
      array(
        'class' => 'persistent-option',
      ),
      array(
        // @NOTE: We disabled this to avoid multiple states
        // javelin_tag(
        //   'input',
        //   array(
        //     'type' => 'checkbox',
        //     'checked' => ($show) ? 'checked' : null,
        //     'value' => !$show,
        //     'sigil' => 'conpherence-persist-column',
        //   )),
        // phutil_tag(
        //   'span',
        //   array(),
        //   pht('Persistent Chat')),
    ));

    return $view;
  }

}
