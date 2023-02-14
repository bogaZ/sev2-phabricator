<?php

final class PHUILobbyView
  extends AphrontTagView {

  protected function getTagName() {
    return null;
  }

  protected function getTagAttributes() {
    return array();
  }

  protected function getTagContent() {
    require_celerity_resource('phabricator-lobby-css');
    require_celerity_resource('javelin-behavior-join-lobby');
    $viewer = $this->getViewer();

    $lobby = id(new PhabricatorApplicationQuery())
      ->setViewer($viewer)
      ->withClasses(array('PhabricatorLobbyApplication'))
      ->executeOne();
    $lobby_uri = $lobby->getApplicationUri('join');
    $lobby_reload = $lobby->getApplicationUri('reload');
    $lobby_leave = $lobby->getApplicationUri('leave');

    Javelin::initBehavior(
      'join-lobby',
      array(
        'device' => 'phone',
        'uri' => $lobby_uri,
        'reload_uri' => $lobby_reload,
        'leave_uri' => $lobby_leave,
      ));

    $user = $this->getViewer();

    $need_attention = $this->createPanel('Need attention');
    $actionable_items = $this->buildLastJoinedChannelInfo();

    $need_attention->appendChild(phutil_implode_html('',
      array_filter($actionable_items)));

    $main_panel = phutil_tag(
      'div',
      array(
        'class' => 'homepage-main-panel',
        'id' => 'lobby-main-pane'
      ), $this->buildAllChannelsPanel($user));
    $layouts = array($actionable_items, $main_panel);

    $dashboard = id(new AphrontMultiColumnView())
      ->setFluidlayout(true)
      ->setGutter(AphrontMultiColumnView::GUTTER_LARGE);

    $dashboard->addColumn(phutil_implode_html('', $layouts), 'thirds');

    $side_panel = phutil_tag(
      'div',
      array(
        'class' => 'homepage-side-panel',
        'id' => 'lobby-side-pane'
      ),
      array(
        $this->buildLobbyPanel(),
        $this->buildBreakPanel(),
        $this->buildInactivePanel(),
      ));
    $dashboard->addColumn($side_panel, 'third');

    $view = id(new PHUIBoxView())
      ->addClass('dashboard-view')
      ->appendChild($dashboard);

    return $view;
  }

  protected function createHandle(ConpherenceThread $thread) {
    $handle = new PhabricatorObjectHandle();
    $handle->setName($thread->getTitle());
    $handle->setFullName($thread->getTopic());
    $handle->setType(PhabricatorConpherenceThreadPHIDType::TYPECONST);
    $handle->setPHID($thread->getPHID());
    $handle->setURI('/Z'.$thread->getId());

    return $handle;
  }

  private function createPanel($header) {
    $panel = new PHUIBoxView();
    $panel->addClass('grouped');
    $panel->addClass('ml');
    return $panel;
  }

  public function buildAllChannelsPanel(PhabricatorUser $user,
    $render = false) {
    $layouts = array();

    $all_in_channels = id(new LobbyStateQuery())
                        ->setViewer($user)
                        ->needOwner(true)
                        ->withStatus(LobbyState::STATUS_IN_CHANNEL)
                        ->withIsWorking(true)
                        ->execute();

    $hq_header = id(new PHUIHeaderView())
      ->setHeaderIcon('fa-building-o')
      ->setHeader(pht('Pilot'));
    $layouts[] = $hq_header->render();
    $layouts[] = phutil_tag('br', array(), null);
    $layouts[] = $this->renderHQ($all_in_channels);
    $layouts[] = phutil_tag('br', array(), null);

    $button = id(new PHUIButtonView())
      ->setTag('a')
      ->setText(pht('Add New'))
      ->setIcon('fa-plus')
      ->setWorkflow(true)
      ->setHref('/conpherence/edit/');
    $team_header = id(new PHUIHeaderView())
      ->setHeaderIcon('fa-briefcase')
      ->addActionLink($button)
      ->setHeader(pht('Teams & Projects'));
    $layouts[] = $team_header->render();

    $elements = array();

    foreach ($all_in_channels as $in_channel) {
      $conpherence = $in_channel->loadChannel();

      if ($conpherence->getIsHQ()) continue;

      $participating = $conpherence->getParticipantIfExists($user->getPHID());

      $could_join = !empty($participating);

      $conpherence_handle = $this->createHandle($conpherence);

      $thread_phid = $conpherence->getPHID();

      $owner = $in_channel->getOwner();
      $current_task = $in_channel->getCurrentTask()
                      ? $in_channel->getCurrentTask()
                      : 'Just mingling';

      if (isset($elements[$thread_phid])) {
        // Add participant data
        $current_card = $elements[$thread_phid];
        $current_card->addBadge(id(new PHUIBadgeMiniView())
            ->setImage($owner->getProfileImageURI())
            ->setHeader(pht('%s : %s',
              $owner->getUserName(),
              $current_task)));

        $elements[$thread_phid] = $current_card;
      } else {
        $new_card = id(new PHUIHovercardView())
          ->setObjectHandle($conpherence_handle)
          ->addBadge(id(new PHUIBadgeMiniView())
            ->setImage($owner->getProfileImageURI())
            ->setHeader(pht('%s : %s',
              $owner->getUserName(),
              $current_task)))
          ->addField(pht('Topic'), $conpherence->getTopic())
          ->addAction(pht('Join'),
                      '/'.$conpherence->getMonogram(),
                      false,
                      !$could_join)
          ->setUser($user);
        $elements[$thread_phid] = $new_card;
      }
    }

    $channels = array();
    foreach ($elements as $channel_phid => $channel_box) {
      $panel = $this->createPanel($channel_phid);
      $panel->appendChild($channel_box);
      $channels[] = $panel;
    }

    $all_boxes = array_chunk($channels, 2);

    foreach ($all_boxes as $boxes) {
      $box1 = isset($boxes[0]) ? $boxes[0] : '';
      $box2 = isset($boxes[1]) ? $boxes[1] : '';

      $row = id(new AphrontMultiColumnView())
        ->addColumn($box1)
        ->addColumn($box2)
        ->setFluidLayout(true)
        ->setFluidLayout(true)
        ->setGutter(AphrontMultiColumnView::GUTTER_MEDIUM);

      if ($render) {
        $row = $row->render();
      }
      $layouts[] = $row;
    }

    if (empty($all_boxes)) {
      $layouts[] = $this->renderEmptyChannels();
    }

    return $layouts;
  }

  public function buildLastJoinedChannelInfo() {
    $states = id(new LobbyStateQuery())
              ->setViewer(PhabricatorUser::getOmnipotentUser())
              ->withOwnerPHIDs(array($this->getViewer()->getPHID()))
              ->needOwner(true)
              ->execute();
    $state = head($states);

    if ($state) {
      $thread = $state->loadChannel();

      if ($thread) {
        $button = id(new PHUIButtonView())
          ->setTag('a')
          ->setText(pht('Back to channel'))
          ->setHref('/'.$thread->getMonogram());
        $btn = new PHUIInfoView();
        $btn->setSeverity(PHUIInfoView::SEVERITY_NODATA);
        $btn->appendChild(pht('You were at #%s before back to lobby',
          $thread->getTitle()));
        $btn->addButton($button);

        return array($btn, phutil_tag('br', array(), null));
      }
    }

    return array();
  }

  public function buildLobbyPanel() {
    $panel = $this->newQueryPanel()
      ->setName(pht('In Lobby'))
      ->setProperty('class', 'LobbyStateSearchEngine')
      ->setProperty('key', 'lobby')
      ->setProperty('limit', 5);

    return $this->renderPanel($panel);
  }

  public function buildBreakPanel() {
    $panel = $this->newQueryPanel()
      ->setName(pht('Work Can Wait'))
      ->setProperty('class', 'LobbyStateSearchEngine')
      ->setProperty('key', 'break')
      ->setProperty('limit', 5);

    return $this->renderPanel($panel);
  }

  public function buildInactivePanel() {
    $panel = $this->newQueryPanel()
      ->setName(pht('Not Available'))
      ->setProperty('class', 'LobbyStateSearchEngine')
      ->setProperty('key', 'unavailable')
      ->setProperty('limit', 5);

    return $this->renderPanel($panel);
  }

  public function renderHQ($all_states) {
    $user = $this->getViewer();
    $pilot_hq = id(new ConpherenceThreadQuery())
                  ->setViewer($user)
                  ->withPilotHQ(true)
                  ->needProfileImage(true)
                  ->execute();

    $view = null;
    $hq = head($pilot_hq);
    if ($hq) {
      $hq_handle = $this->createHandle($hq);
      $hq_handle->setImageURI($hq->getProfileImageURI());
      $hq_view = id(new PHUIHovercardView())
        ->setObjectHandle($hq_handle)
        ->addField(pht('Topic'), $hq->getTopic())
        ->addAction(pht('Join'),
                    '/'.$hq->getMonogram(),
                    false,
                    false)
        ->setUser($user);

      foreach($all_states as $state) {
        if ($state->getCurrentChannel() != $hq->getPHID()) continue;
        $owner = $state->getOwner();
        $current_task = $state->getCurrentTask();
        if (empty($current_task)) $current_task = LobbyState::DEFAULT_TASK;
        $hq_view->addBadge(id(new PHUIBadgeMiniView())
            ->setImage($owner->getProfileImageURI())
            ->setHeader(pht('%s : %s',
              $owner->getUserName(),
              $current_task)));
      }

      $view = $hq_view;
    } else {
      $button = id(new PHUIButtonView())
        ->setTag('a')
        ->setText(pht('Launch Pilot'))
        ->setColor(PHUIButtonView::GREEN)
        ->setWorkflow(true)
        ->setHref('/lobby/pilot');
      return id(new PHUIBigInfoView())
        ->setTitle(pht('Houston, May Day!'))
        ->setIcon('fa-fighter-jet')
        ->setDescription(pht('We need a Pilot'))
        ->addAction($button);
    }

    return $view->render();
  }

  public function renderEmptyChannels() {
    return id(new PHUIBigInfoView())
      ->setTitle(pht('Hey-Ho!'))
      ->setIcon('fa-microphone-slash')
      ->setDescription(pht('We\'re safe and sound'))->render();
  }

  private function newQueryPanel() {
    $panel_type = id(new PhabricatorDashboardQueryPanelType())
      ->getPanelTypeKey();

    return id(new PhabricatorDashboardPanel())
      ->setPanelType($panel_type);
  }

  private function renderPanel(PhabricatorDashboardPanel $panel) {
    $viewer = $this->getViewer();

    return id(new PhabricatorDashboardPanelRenderingEngine())
      ->setViewer($viewer)
      ->setPanel($panel)
      ->setParentPanelPHIDs(array())
      ->renderPanel();
  }
}
