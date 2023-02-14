<?php

final class ConpherenceLayoutView extends AphrontTagView {

  private $thread;
  private $baseURI;
  private $threadView;
  private $role;
  private $header;
  private $search;
  private $messages;
  private $replyForm;
  private $theme = ConpherenceRoomSettings::COLOR_LIGHT;
  private $latestTransactionID;

  public function setMessages($messages) {
    $this->messages = $messages;
    return $this;
  }

  public function setReplyForm($reply_form) {
    $this->replyForm = $reply_form;
    return $this;
  }

  public function setHeader($header) {
    $this->header = $header;
    return $this;
  }

  public function setSearch($search) {
    $this->search = $search;
    return $this;
  }

  public function setRole($role) {
    $this->role = $role;
    return $this;
  }

  public function getThreadView() {
    return $this->threadView;
  }

  public function setBaseURI($base_uri) {
    $this->baseURI = $base_uri;
    return $this;
  }

  public function setThread(ConpherenceThread $thread) {
    $this->thread = $thread;
    return $this;
  }

  public function setThreadView(ConpherenceThreadListView $thead_view) {
    $this->threadView = $thead_view;
    return $this;
  }

  public function setTheme($theme) {
    $this->theme = $theme;
    return $this;
  }

  public function setLatestTransactionID($id) {
    $this->latestTransactionID = $id;
    return $this;
  }

  protected function getTagAttributes() {
    $classes = array();
    $classes[] = 'conpherence-layout';
    $classes[] = 'hide-widgets';
    $classes[] = 'conpherence-role-'.$this->role;
    $classes[] = ConpherenceRoomSettings::getThemeClass($this->theme);

    return array(
      'id'    => 'conpherence-main-layout',
      'sigil' => 'conpherence-layout',
      'class' => implode(' ', $classes),
    );
  }

  protected function getTagContent() {
    require_celerity_resource('conpherence-menu-css');
    require_celerity_resource('conpherence-message-pane-css');
    require_celerity_resource('conpherence-participant-pane-css');

    $selected_id = null;
    $selected_thread_id = null;
    $selected_thread_phid = null;
    $can_edit_selected = null;
    $nux = null;
    if ($this->thread) {
      $selected_id = $this->thread->getPHID().'-nav-item';
      $selected_thread_id = $this->thread->getID();
      $selected_thread_phid = $this->thread->getPHID();
      $can_edit_selected = PhabricatorPolicyFilter::hasCapability(
        $this->getUser(),
        $this->thread,
        PhabricatorPolicyCapability::CAN_EDIT);
    } else {
      $nux = $this->buildNUXView();
    }
    $this->initBehavior('conpherence-menu',
      array(
        'baseURI' => $this->baseURI,
        'layoutID' => 'conpherence-main-layout',
        'selectedID' => $selected_id,
        'selectedThreadID' => $selected_thread_id,
        'selectedThreadPHID' => $selected_thread_phid,
        'canEditSelectedThread' => $can_edit_selected,
        'latestTransactionID' => $this->latestTransactionID,
        'role' => $this->role,
        'theme' => ConpherenceRoomSettings::getThemeClass($this->theme),
        'hasThreadList' => (bool)$this->threadView,
        'hasThread' => (bool)$this->messages,
        'hasWidgets' => false,
      ));

    if ($this->getViewer()->isOmnipotent()) {
      $participant_pane = javelin_tag(
        'div',
        array(
          'class' => 'conpherence-participant-pane',
          'id' => 'conpherence-participant-pane',
          'sigil' => 'conpherence-participant-pane',
        ),
        array(
          phutil_tag(
            'div',
            array(
              'class' => 'widgets-loading-mask',
            ),
            'Download Mobile'),
          javelin_tag(
            'div',
            array(
              'sigil' => 'conpherence-widgets-holder',
            ),
            'Download Desktop'),

          javelin_tag(
            'div',
            array(
              'class' => $this->thread->getPHID().'-nav-item',
              'id' => $this->thread->getPHID().'-nav-item',
              'sigil' => $this->thread->getPHID().'-nav-item',
            ),
            'Nav'),
        ));
    } else {
      $participant_pane = javelin_tag(
        'div',
        array(
          'class' => 'conpherence-participant-pane',
          'id' => 'conpherence-participant-pane',
          'sigil' => 'conpherence-participant-pane',
        ),
        array(
          phutil_tag(
            'div',
            array(
              'class' => 'widgets-loading-mask',
            ),
            ''),
          javelin_tag(
            'div',
            array(
              'sigil' => 'conpherence-widgets-holder',
            ),
            ''),
        ));
        $this->initBehavior('conpherence-participant-pane');
    }

    return
      array(
        javelin_tag(
          'div',
          array(
            'id' => 'conpherence-menu-pane',
            'class' => 'conpherence-menu-pane phabricator-side-menu',
            'sigil' => 'conpherence-menu-pane',
          ),
          $this->threadView),
        javelin_tag(
          'div',
          array(
            'class' => 'conpherence-content-pane',
          ),
          array(
            phutil_tag(
              'div',
              array(
                'class' => 'conpherence-loading-mask',
              ),
              $this->buildLoadingView()),
            javelin_tag(
              'div',
              array(
                'class' => 'conpherence-header-pane',
                'id' => 'conpherence-header-pane',
                'sigil' => 'conpherence-header-pane',
              ),
              nonempty($this->header, '')),
            javelin_tag(
              'div',
              array(
                'class' => 'conpherence-no-threads',
                'sigil' => 'conpherence-no-threads',
                'style' => 'display: none;',
              ),
              $nux),
            $participant_pane,
            javelin_tag(
              'div',
              array(
                'class' => 'conpherence-message-pane',
                'id' => 'conpherence-message-pane',
                'sigil' => 'conpherence-message-pane',
              ),
              array(
                javelin_tag(
                  'div',
                  array(
                    'class' => 'conpherence-messages',
                    'id' => 'conpherence-messages',
                    'sigil' => 'conpherence-messages',
                  ),
                  nonempty($this->messages, '')),
                javelin_tag(
                  'div',
                  array(
                    'class' => 'conpherence-search-main',
                    'id' => 'conpherence-search-main',
                    'sigil' => 'conpherence-search-main',
                  ),
                  nonempty($this->search, '')),
                phutil_tag(
                  'div',
                  array(
                    'class' => 'messages-loading-mask',
                  ),
                  ''),
                javelin_tag(
                  'div',
                  array(
                    'id' => 'conpherence-form',
                    'sigil' => 'conpherence-form',
                  ),
                  nonempty($this->replyForm, '')),
              )),
          )),
      );
  }

  private function buildNUXView() {
    $viewer = $this->getViewer();

    $engine = id(new ConpherenceThreadSearchEngine())
      ->setViewer($viewer);
    $saved = $engine->buildSavedQueryFromBuiltin('all');
    $query = $engine->buildQueryFromSavedQuery($saved);
    $pager = $engine->newPagerForSavedQuery($saved)
      ->setPageSize(10);
    $results = $engine->executeQuery($query, $pager);
    $view = $engine->renderResults($results, $saved);

    $create_button = id(new PHUIButtonView())
      ->setTag('a')
      ->setText(pht('New Room'))
      ->setHref('/conpherence/new/')
      ->setWorkflow(true)
      ->setColor(PHUIButtonView::GREEN);

    if ($results) {
      $create_button->setIcon('fa-comments');

      $header = id(new PHUIHeaderView())
        ->setHeader(pht('Joinable Rooms'))
        ->addActionLink($create_button);

      $box = id(new PHUIObjectBoxView())
        ->setHeader($header)
        ->setObjectList($view->getContent());

      return $box;
    } else {

      $view = id(new PHUIBigInfoView())
        ->setIcon('fa-comments')
        ->setTitle(pht('Welcome to Conpherence'))
        ->setDescription(
          pht('Conpherence lets you create public or private rooms to '.
            'communicate with others.'))
        ->addAction($create_button);

        return $view;
    }
  }

  protected function buildLoadingView() {
    $current_user = $this->getViewer();

    if ($current_user->isOmnipotent()) {
      $image = celerity_get_resource_uri('/rsrc/image/avatar.png');
      $title = pht('Howdy you! %s', $this->greet());
    } else {
      $current_user->loadUserProfile();
      $image = $current_user->getProfileImageURI();
      $title = pht('Howdy %s! %s',
        $current_user->getRealName(),
        $this->greet());
    }

    return phutil_tag('span', array(
      'class' => 'loading-greet'
    ), id(new PHUIBigInfoView())
      ->setIcon('fa-gift')
      ->setImage($image)
      ->setTitle($title)
      ->setDescription(pht('-  your faithfully, polite, Suite Robot')));
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
