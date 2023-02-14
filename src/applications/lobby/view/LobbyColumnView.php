<?php

final class LobbyColumnView extends AphrontTagView {

  private $conpherences = array();
  private $draft;
  private $selectedConpherence;
  private $transactions;
  private $visible;
  private $initialLoad = false;
  private $policyObjects;
  private $quicksandConfig = array();
  private $user;

  public function setConpherences(array $conpherences) {
    assert_instances_of($conpherences, 'ConpherenceThread');
    $this->conpherences = $conpherences;
    return $this;
  }

  public function getConpherences() {
    return $this->conpherences;
  }

  public function setDraft(PhabricatorDraft $draft) {
    $this->draft = $draft;
    return $this;
  }

  public function getDraft() {
    return $this->draft;
  }

  public function setUser(PhabricatorUser $user) {
    $this->user = $user;
    return $this;
  }

  protected function getUser() {
    return $this->user;
  }

  public function setSelectedConpherence(
    ConpherenceThread $conpherence = null) {
    $this->selectedConpherence = $conpherence;
    return $this;
  }

  public function getSelectedConpherence() {
    return $this->selectedConpherence;
  }

  public function setTransactions(array $transactions) {
    assert_instances_of($transactions, 'ConpherenceTransaction');
    $this->transactions = $transactions;
    return $this;
  }

  public function getTransactions() {
    return $this->transactions;
  }

  public function setVisible($visible) {
    $this->visible = $visible;
    return $this;
  }

  public function getVisible() {
    return $this->visible;
  }

  public function setInitialLoad($bool) {
    $this->initialLoad = $bool;
    return $this;
  }

  public function getInitialLoad() {
    return $this->initialLoad;
  }

  public function setPolicyObjects(array $objects) {
    assert_instances_of($objects, 'PhabricatorPolicy');

    $this->policyObjects = $objects;
    return $this;
  }

  public function getPolicyObjects() {
    return $this->policyObjects;
  }

  public function setQuicksandConfig(array $config) {
    $this->quicksandConfig = $config;
    return $this;
  }

  public function getQuicksandConfig() {
    return $this->quicksandConfig;
  }

  protected function getTagAttributes() {
    // if ($this->getVisible()) {
      $style = null;
    // } else {
      // $style = 'display: none;';
    // }
    $classes = array('lobby-chat-container');
    if ($this->getInitialLoad()) {
      $classes[] = 'loading';
    }

    return array(
      'id' => 'lobby-chat-container',
      'class' => implode(' ', $classes),
      'style' => $style,
      'sigil' => 'lobby-chat-container',
    );
  }

  protected function getTagContent() {
    $column_key = PhabricatorConpherenceColumnVisibleSetting::SETTINGKEY;

    // Javelin::initBehavior(
    //   'durable-column',
    //   array(
    //     'visible' => $this->getVisible(),
    //     'minimize' => $this->getMinimize(),
    //     'visibleURI' => '/settings/adjust/?key='.$column_key,
    //     'minimizeURI' => '/settings/adjust/?key='.$minimize_key,
    //     'quicksandConfig' => $this->getQuicksandConfig(),
    //   ));

    $policy_objects = ConpherenceThread::loadViewPolicyObjects(
      $this->getUser(),
      $this->getConpherences());
    $this->setPolicyObjects($policy_objects);

    $classes = array();
    $classes[] = 'conpherence-durable-column-header';
    $classes[] = 'grouped';

    $header = phutil_tag(
      'div',
      array(
        'class' => implode(' ', $classes),
        'data-sigil' => 'conpherence-header',
      ),
      $this->buildHeader());

    $transactions = $this->buildTransactions();

    $content = javelin_tag(
      'div',
      array(
        'class' => 'conpherence-durable-column-main',
        'sigil' => 'conpherence-durable-column-main',
      ),
      phutil_tag(
        'div',
        array(
          'id' => 'conpherence-durable-column-content',
          'class' => 'conpherence-durable-column-frame',
        ),
        javelin_tag(
          'div',
          array(
            'class' => 'conpherence-durable-column-transactions',
            'sigil' => 'conpherence-durable-column-transactions',
          ),
          $transactions)));

    $input = $this->buildTextInput();

    return array(
      $header,
      javelin_tag(
        'div',
        array(
          'class' => 'lobby-chat',
          'sigil' => 'lobby-chat',
        ),
        array(
          $content,
          $input,
        )),
    );
  }

  private function buildHeader() {
    $conpherence = $this->getSelectedConpherence();

    $bubble_id = celerity_generate_unique_node_id();
    $dropdown_id = celerity_generate_unique_node_id();

    $settings_list = new PHUIListView();
    $header_actions = $this->getHeaderActionsConfig($conpherence);
    foreach ($header_actions as $action) {
      $settings_list->addMenuItem(
        id(new PHUIListItemView())
        ->setHref($action['href'])
        ->setName($action['name'])
        ->setIcon($action['icon'])
        ->setDisabled($action['disabled'])
        ->addSigil('conpherence-durable-column-header-action')
        ->setMetadata(array(
          'action' => $action['key'],
        )));
    }

    $settings_menu = phutil_tag(
      'div',
      array(
        'id' => $dropdown_id,
        'class' => 'phabricator-main-menu-dropdown phui-list-sidenav '.
        'conpherence-settings-dropdown',
        'sigil' => 'phabricator-notification-menu',
        'style' => 'display: none',
      ),
      $settings_list);

    Javelin::initBehavior(
      'aphlict-dropdown',
      array(
        'bubbleID' => $bubble_id,
        'dropdownID' => $dropdown_id,
        'local' => true,
        'containerDivID' => 'lobby-chat-container',
      ));

    $bars = id(new PHUIListItemView())
      ->setName(pht('Room Actions'))
      ->setIcon('fa-gear')
      ->addClass('core-menu-item')
      ->addClass('conpherence-settings-icon')
      ->addSigil('conpherence-settings-menu')
      ->setID($bubble_id)
      ->setHref('#')
      ->setAural(pht('Room Actions'))
      ->setOrder(400);

    $settings_button = id(new PHUIListView())
      ->addMenuItem($bars)
      ->addClass('phabricator-application-menu');

    if ($conpherence) {
      $data = $conpherence->getDisplayData($this->getUser());
      $header = phutil_tag(
        'span',
        array(),
        'Chat');
    } else {
      $header = phutil_tag(
        'span',
        array(),
        pht('Conpherence'));
    }

    $status = new PhabricatorNotificationStatusView();

    return
      phutil_tag(
        'div',
        array(
          'class' => 'conpherence-durable-column-header-inner',
        ),
        array(
          $settings_button,
          $settings_menu,
          javelin_tag(
            'div',
            array(
              'sigil' => 'conpherence-durable-column-header-text',
              'class' => 'conpherence-durable-column-header-text',
            ),
            $header),
        ));
  }

  private function getHeaderActionsConfig($conpherence) {

    $actions = array();
    if ($conpherence) {
      $can_edit = PhabricatorPolicyFilter::hasCapability(
        $this->getUser(),
        $conpherence,
        PhabricatorPolicyCapability::CAN_EDIT);
      $actions[] = array(
        'name' => pht('Add Participants'),
        'disabled' => !$can_edit,
        'href' => '/conpherence/update/'.$conpherence->getID().'/',
        'icon' => 'fa-plus',
        'key' => ConpherenceUpdateActions::ADD_PERSON,
      );
      $actions[] = array(
        'name' => pht('Edit Room'),
        'disabled' => !$can_edit,
        'href' => '/conpherence/edit/'.$conpherence->getID().'/',
        'icon' => 'fa-pencil',
        'key' => 'go_edit',
      );
    }

    return $actions;
  }

  private function buildTransactions() {
    $conpherence = $this->getSelectedConpherence();
    if (!$conpherence) {
      if (!$this->getVisible() || $this->getInitialLoad()) {
        return pht('Loading...');
      }
      $view = array(
        phutil_tag(
          'div',
          array(
            'class' => 'column-no-rooms-text',
          ),
          pht('You have not joined any rooms yet.')),
        javelin_tag(
          'a',
          array(
            'href' => '/conpherence/search/',
            'class' => 'button button-grey',
          ),
          pht('Find Rooms')),
      );
      return phutil_tag_div('column-no-rooms', $view);
    }

    $data = ConpherenceTransactionRenderer::renderTransactions(
      $this->getUser(),
      $conpherence);
    $messages = ConpherenceTransactionRenderer::renderMessagePaneContent(
      $data['transactions'],
      $data['oldest_transaction_id'],
      $data['newest_transaction_id']);

    return $messages;
  }

  private function buildTextInput() {
    $conpherence = $this->getSelectedConpherence();
    if (!$conpherence) {
      return null;
    }
    $id = $conpherence->getID();

    $draft = $this->getDraft();
    $draft_value = null;
    if ($draft) {
      $draft_value = $draft->getDraft();
    }

    $update_uri = '/conpherence/update/'.$id.'/';

    $action = ConpherenceUpdateActions::MESSAGE;
    $status = new PhabricatorNotificationStatusView();

    $form = id(new AphrontFormView())
      ->setUser($this->getUser())
      ->setAction($update_uri)
      ->addSigil('conpherence-pontificate')
      ->setWorkflow(true)
      ->addHiddenInput('action', $action)
      ->appendChild(
        id(new PhabricatorRemarkupControl())
        ->setUser($this->getUser())
        ->setName('text')
        ->setSendOnEnter(true)
        ->setValue($draft->getDraft()));

    $status_view = phutil_tag(
      'div',
      array(
        'class' => 'conpherence-room-status',
        'id' => 'conpherence-room-status',
      ),
      $status);

    return phutil_tag_div(
      'pontificate-container', array($form, $status_view));
  }

  private function buildStatusText() {
    return null;
  }

  private function buildSendButton() {
    $conpherence = $this->getSelectedConpherence();
    if (!$conpherence) {
      return null;
    }

    return javelin_tag(
      'button',
      array(
        'class' => 'grey',
        'sigil' => 'conpherence-send-message',
      ),
      pht('Send'));
  }

}
