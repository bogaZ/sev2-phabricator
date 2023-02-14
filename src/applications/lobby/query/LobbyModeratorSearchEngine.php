<?php

final class LobbyModeratorSearchEngine
  extends PhabricatorApplicationSearchEngine {

  public function getResultTypeDescription() {
    return pht('Lobby Moderator');
  }

  public function getApplicationClassName() {
    return 'PhabricatorLobbyApplication';
  }

  public function newQuery() {
    return id(new LobbyModeratorQuery())
            ->needModerator(true);
  }

  protected function buildQueryFromParameters(array $map) {
    $query = $this->newQuery();

    if ($map['channelPHIDs']) {
      $query->withChannelPHIDs($map['channelPHIDs']);
    }

    if ($map['moderatorPHIDs']) {
      $query->withModeratorPHIDs($map['moderatorPHIDs']);
    }

    return $query;
  }

  protected function buildCustomSearchFields() {
    return array(
      id(new PhabricatorUsersSearchField())
        ->setKey('moderatorPHIDs')
        ->setAliases(array('users'))
        ->setLabel(pht('Users')),
      id(new LobbyConpherenceSearchField())
        ->setKey('channelPHIDs')
        ->setAliases(array('Channels'))
        ->setLabel(pht('Channels')),

    );
  }

  protected function getURI($path) {
    return '/lobby/moderators/'.$path;
  }

  protected function getBuiltinQueryNames() {
    $names = array(
      'all' => pht('All'),
    );

    return $names;
  }

  public function buildSavedQueryFromBuiltin($query_key) {
    $query = $this->newSavedQuery();
    $query->setQueryKey($query_key);

    switch ($query_key) {
      case 'all':
        return $query;
    }

    return parent::buildSavedQueryFromBuiltin($query_key);
  }

  protected function getRequiredHandlePHIDsForResultList(
    array $states,
    PhabricatorSavedQuery $query) {
    return mpull($states, 'getModeratorPHID');
  }

  protected function renderResultList(
    array $moderators,
    PhabricatorSavedQuery $query,
    array $handles) {
    assert_instances_of($moderators, 'LobbyModerator');

    $viewer = $this->requireViewer();

    $list = id(new PHUIObjectItemListView())
      ->setUser($viewer);

    $app = id(new PhabricatorApplicationQuery())
      ->setViewer($viewer)
      ->withClasses(array('PhabricatorLobbyApplication'))
      ->executeOne();

    $can_manage = PhabricatorPolicyFilter::hasCapability(
      $viewer,
      $app,
      LobbyManageCapability::CAPABILITY);

    foreach ($moderators as $moderator) {
      $user = $moderator->getModerator();

      $item = new PHUIObjectItemView();
      $item->setHeader($user->getFullName())
        ->setHref('/p/'.$user->getUserName().'/')
        ->addAttribute(phabricator_datetime($moderator->getDateModified(),
          $viewer))
        ->addAttribute('#'.$moderator->loadChannel()->getTitle())
        ->setImageURI($user->getProfileImageURI());


      if ($can_manage) {
        $moderator_id = $moderator->getID();
        $item->addAction(
          id(new PHUIListItemView())
            ->setIcon('fa-ban')
            ->setName(pht('Disable'))
            ->setWorkflow(true)
            ->setHref($this->getApplicationURI(
              'moderators/disable/'.$moderator_id.'/')));
      }

      $list->addItem($item);
    }

    $result = new PhabricatorApplicationSearchResultView();
    $result->setObjectList($list);
    $result->setNoDataString(pht('No one found.'));

    return $result;
  }

  protected function getNewUserBody() {
    $create_button = id(new PHUIButtonView())
      ->setTag('a')
      ->setText(pht('Add a Moderator'))
      ->setHref('/lobby/moderators/edit/form/default')
      ->setColor(PHUIButtonView::GREEN);

    $icon = $this->getApplication()->getIcon();
    $app_name =  $this->getApplication()->getName();
    $view = id(new PHUIBigInfoView())
      ->setIcon($icon)
      ->setTitle(pht('Welcome to %s', $app_name))
      ->setDescription(
        $this->getApplication()->getShortDescription())
      ->addAction($create_button);

      return $view;
  }
}
