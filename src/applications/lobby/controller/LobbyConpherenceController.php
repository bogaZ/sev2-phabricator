<?php

final class LobbyConpherenceController
  extends LobbyController {

  public function handleRequest(AphrontRequest $request) {
    $ok = $this->metRequiredCapabilities(false);
    $old_message_id = $request->getURIData('messageID');

    if (!$ok || $old_message_id) {
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

    require_celerity_resource('phabricator-lobby-conpherence-css');

    $user = $this->getViewer();
    $conpherence_id = $request->getURIData('id');
    if (!$conpherence_id) {
      return new Aphront404Response();
    }
    $conpherences = id(new ConpherenceThreadQuery())
      ->setViewer($user)
      ->withIDs(array($conpherence_id))
      ->needProfileImage(true)
      ->needTransactions(true)
      ->execute();

    $conpherence = head($conpherences);
    if (!$conpherence) {
      return new Aphront404Response();
    }

    // Ensure membership
    if (!$conpherence->getParticipantIfExists($user->getPHID())) {
      $xactions = array();
      $xactions[] = id(new ConpherenceTransaction())
        ->setTransactionType(
          ConpherenceThreadParticipantsTransaction::TRANSACTIONTYPE)
        ->setNewValue(array('+' => array($user->getPHID())));

      id(new ConpherenceEditor())
        ->setActor($user)
        ->setContentSource(PhabricatorContentSource::newFromRequest(
          $request))
        ->setContinueOnNoEffect(true)
        ->applyTransactions($conpherence, $xactions);
    }

    // Update state
    id(new Lobby())
        ->setViewer($user)
        ->joinChannel(
      $user,
      PhabricatorContentSource::newFromRequest($request),
      $conpherence->getPHID());

    $nav = new AphrontRightSideNavFilterView();
    $nav->setBaseURI(new PhutilURI($this->getApplicationURI('view/')))
        ->withoutFooter()
        ->selectFilter('lobby');

    // Main menu
    $main_menu = id(new PHUIListItemView())
      ->setHref("#")
      ->setSelected(true)
      ->setCustomSigil('lobby-sidebar-menu-item')
      ->setIcon('fa-comments')
      ->setType(PHUIListItemView::TYPE_ICON);
    $nav->addMenuItem($main_menu);
    $tags_phid = head($conpherences)->getTagsPHID();
    // Sub menus
    // This code use for hidden the goals menu if the conpherence isn't
    // an active project, it will be lock on 5th array, so if someday need
    // improvement or add some feature in conpherence you can change this code
    $items = $this->buildMenuItems($conpherence_id);
    if (!$tags_phid) {
      unset($items[4]);
    }
    $icons = $this->buildMenuIcons();
    foreach($items as $i => $menu_item) {
      $menu_item
          ->setIcon($icons[$i])
          ->setName('')
          ->setCustomSigil('lobby-sidebar-menu-item')
          ->setType(PHUIListItemView::TYPE_ICON);
      $nav->addMenuItem($menu_item);
    }

    $box = phutil_tag(
      'div',
        array(
          'id' => 'lobby-box',
          'class' => 'lobby-box',
        ), array(
          $this->buildBoxHeader($conpherence),
          $this->buildBoxBody(),
          $this->buildBoxFooter($conpherence),
        ));

    $sidebar_canvas = phutil_tag(
      'div',
        array(
          'id' => 'lobby-utility',
          'class' => 'lobby-utility',
          'data-sigil' => 'lobby-utility',
        ), phutil_tag(
          'div',
          array(
            'id' => 'lobby-utility-body',
            'class' => 'lobby-utility-body'
          ),
          phutil_tag(
            'span',
            array(
              'class' => 'lobby-utility-loading'
            ), 'Loading...'
          )
        ));

    $utility = phutil_tag(
      'div',
        array(
          'id' => 'lobby-utility-container',
          'class' => 'lobby-utility-container',
        ), $sidebar_canvas);

    $chat_durable = phutil_tag(
      'div',
        array(
          'id' => 'lobby-chat',
          'class' => 'lobby-chat',
          'data-sigil' => 'lobby-chat',
        ),phutil_tag(
          'div',
          array(
            'data-sigil' => 'conpherence-durable-column-main',
            'class' => 'conpherence-durable-column-main',
          ), id(new PHUIBigInfoView())
            ->setIcon('fa-gift')
            ->setImage($user->getProfileImageURI())
            ->setTitle(pht('Howdy %s! %s',
              $user->getRealName(),
              $this->greet()))
            ->setDescription(pht('-  your faithfully, polite, Suite Robot'))));

    $chat = phutil_tag(
      'div',
        array(
          'id' => 'lobby-chat-container',
          'class' => 'lobby-chat-container',
        ),
        $chat_durable, phutil_tag(
          'div',
          array(
            'data-sigin' => 'conpherence-durable-column-main'
          )
        ));

    $lobby_container = phutil_tag(
      'div',
      array(
        'id' => 'lobby-container',
        'class' => 'lobby-container'
      ),
      array($box, $chat, $utility));
    $nav->appendChild($lobby_container);

    Javelin::initBehavior(
      'prepare-side-menu',
      array()
    );

    Javelin::initBehavior(
      'prepare-chat',
      array(
        'threadID' => $conpherence_id,
        'quicksandConfig' => array()
      ));


    Javelin::initBehavior(
      'reaction',
      array());

    return $this->newPage()
      ->setTitle($conpherence->getTitle())
      ->appendChild($nav);
  }

  protected function requiresManageCapability() {
    return false;
  }

  protected function requiresJoinCapability() {
    return true;
  }

  protected function delegateToConpherence() {
    $request = $this->getRequest();

    $controller = id(new ConpherenceViewController());
    $controller->setDelegatingController($this);
    $controller->setRequest($request);

    $application = $this->getCurrentApplication();
    if ($application) {
      $controller->setCurrentApplication($application);
    }

    return $controller->handleRequest($request);
  }

  public function buildApplicationMenu() {
    $id = $this->getRequest()->getURIData('id');

    $menu = id(new PHUIListView());

    $items = $this->buildMenuItems($id);

    foreach($items as $item) {
      $item->setType(PHUIListItemView::TYPE_LINK);
      $item->setIsExternal(true);
      $menu->addMenuItem($item);
    }

    return $menu;
  }

  protected function buildMenuIcons() {
    return array(
      'fa-thumb-tack',
      'fa-th-list',
      'fa-paperclip',
      'fa-calendar-o',
      'fa-check-square-o',
    );
  }

  protected function buildMenuItems($id) {
    $items = array();

    $items[] = id(new PHUIListItemView())
      ->setName(pht('Stickit'))
      ->setHref("/lobby/conph/stickit/${id}/");

    $items[] = id(new PHUIListItemView())
      ->setName('Tasks')
      ->setHref("/lobby/conph/tasks/${id}/");

    $items[] = id(new PHUIListItemView())
      ->setName('Files')
      ->setHref("/lobby/conph/files/${id}/");

    $items[] = id(new PHUIListItemView())
      ->setName('Calendar')
      ->setHref("/lobby/conph/calendar/${id}/");

    $items[] = id(new PHUIListItemView())
      ->setName('Goals')
      ->setHref("/lobby/conph/goals/${id}/");

    return $items;
  }

  protected function buildBoxHeader(ConpherenceThread $thread) {
    $states = id(new LobbyStateQuery())
              ->setViewer(PhabricatorUser::getOmnipotentUser())
              ->withStatus(LobbyState::STATUS_IN_CHANNEL)
              ->withCurrentChannel($thread->getPHID())
              ->needOwner(true)
              ->execute();

    Javelin::initBehavior(
      'member-state',
      array(
        'threadPHID' => $thread->getPHID(),
        'username' => $this->getRequest()->getViewer()->getPHID(),
        'check_url' => '/lobby/in-channel/'.$thread->getPHID(),
        'sound_url' => celerity_get_resource_uri(
          '/rsrc/audio/basic/ting.mp3'),
      )
    );

    $header = id(new PHUIHeaderView())
              ->setHeader($thread->getTitle())
              ->setSubheader($thread->getTopic())
              ->setImage($thread->getProfileImageURI())
              ->setImageEditURL(pht('/conpherence/picture/%d/',
                  $thread->getID()));

    $members = phutil_tag('div',
      array(
        'id' => 'lobby-box-members',
        'class' => 'lobby-box-members'
      ),
      Lobby::buildInChannelBadges($states, $thread->getPHID()));
    return phutil_tag('div', array(
      'id' => 'lobby-box-header-container',
      'class' => 'lobby-box-header-container',
    ), array($header, $members));
  }

  protected function buildBoxBody() {
    $user = $this->getRequest()->getViewer();
    Javelin::initBehavior(
      'media-state',
      array(
        'user_phid' => $user->getPHID(),
        'user_name' => $user->getRealName(),
        'user_image' => $user->getProfileImageURI(),
      )
    );

    $no_activity = id(new PHUIBigInfoView())
      ->setID('lobby-media-empty-view')
      ->setTitle(pht('Hey-Ho!'))
      ->setIcon('fa-microphone-slash')
      ->setDescription(pht('Its a bit quite here'));

    return phutil_tag('div', array(
      'id' => 'lobby-box-body-container',
      'class' => 'lobby-box-body-container',
    ), array($no_activity));
  }

  protected function buildBoxFooter(ConpherenceThread $thread) {
    $user = $this->getRequest()->getViewer();
    $namespace = PhabricatorEnv::getEnvConfig('sev2.workspace', 'suite');
    Javelin::initBehavior(
      'ice',
      array(
        'user_phid' => $user->getPHID(),
        'user_name' => $user->getRealName(),
        'user_image' => $user->getProfileImageURI(),
        'threadPHID' => $thread->getPHID(),
        'username' => $user->getPHID(),
        'password' => 'tuuuuuurn',
        'stun_url' => 'stun:ice.sev-2.com:3478',
        'turn_url' => 'turn:ice.sev-2.com:3478',
        'ws_base_url' => 'wss://ice.sev-2.com:8443/websocket/'.$namespace.'-',
      )
    );

    $template_audio = javelin_tag(
      'template',
      array(
        'id' => 'audio-template',
        'style' => 'display:none;',
      ),
      javelin_tag(
        'audio',
        array(
          'style' => 'display:none;',
          'autoplay' => 'true',
          // 'muted' => 'muted',
        )
      )
    );

    $media = javelin_tag(
      'div',
      array(
        'class' => 'lobby-media',
        'id' => 'lobby-media',
        'sigil' => 'lobby-media',
      ),
      array($template_audio, javelin_tag(
        'div',
        array(
          'id' => 'audios',
          'style' => 'display:none;',
        ),
        javelin_tag(
          'audio',
          array(
            'id' => 'my-audio',
            'style' => 'display:none;',
            'autoplay' => 'true',
            'muted' => 'muted',
          )
        )
      )));


    $button_bar = new PHUIButtonBarView();

    $speak_btn = id(new PHUIButtonView())
      ->setTag('a')
      ->setColor(PHUIButtonView::RED)
      ->addSigil('lobby-control-toggle-microphone')
      ->setIcon('fa-microphone-slash');
    $button_bar->addButton($speak_btn);

    $share_screen_btn = id(new PHUIButtonView())
      ->setTag('a')
      ->setColor(PHUIButtonView::GREY)
      ->setDisabled(true)
      ->setIcon('fa-desktop');
    $button_bar->addButton($share_screen_btn);

    Javelin::initBehavior(
      'ice-control',
      array(
        'threadPHID' => $thread->getPHID(),
        'username' => $user->getPHID(),
      )
    );

    return phutil_tag('div', array(
      'id' => 'lobby-box-footer-container',
      'class' => 'lobby-box-footer-container',
    ), array($button_bar, $template_audio, $media));
  }

  private function greet() {
    return $this->selectGreet($this->getGreetings());
  }

  private function getGreetings() {
    return array(
      pht('Make each day your masterpiece'),
      pht('You can totally do this'),
      pht('Don\'t stop until you proud'),
      pht('Every day is a second chance'),
      pht('Only dead fish go with the flow'),
      pht('Leap, and the net will appear'),
    );
  }

  private function selectGreet(array $greets) {
    // This is a simple pseudorandom number generator that avoids touching
    // srand(), because it would seed it to a highly predictable value. It
    // selects a new greet every day.

    $seed = ((int)date('Y') * 366) + (int)date('z');
    for ($ii = 0; $ii < 32; $ii++) {
      $seed = ((1664525 * $seed) + 1013904223) % (1 << 31);
    }

    return $greets[$seed % count($greets)];
  }
}
