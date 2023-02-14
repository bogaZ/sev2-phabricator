<?php

final class LobbyStateSearchEngine
  extends PhabricatorApplicationSearchEngine {

  public function getResultTypeDescription() {
    return pht('Lobby State');
  }

  public function getApplicationClassName() {
    return 'PhabricatorLobbyApplication';
  }

  public function newQuery() {
    return id(new LobbyStateQuery())
            ->needOwner(true);
  }

  protected function buildQueryFromParameters(array $map) {
    $query = $this->newQuery();

    if ($map['ownerPHIDs']) {
      $query->withOwnerPHIDs($map['ownerPHIDs']);
    }

    if (isset($map['isWorking'])) {
      $query->withIsWorking($map['isWorking']);
    }

    if (isset($map['status'])) {
      $query->withStatus($map['status']);
    }

    if ($map['currentChannel']) {
      $query->withCurrentChannel($map['currentChannel']);
    }

    if (isset($map['break'])) {
      if ($map['break'] && $map['break'][0] == 'break') {
        $query->withStatusExcluded(array(
          LobbyState::STATUS_IN_LOBBY,
          LobbyState::STATUS_IN_CHANNEL,
        ));
      }
    }

    return $query;
  }

  protected function buildCustomSearchFields() {
    return array(
      id(new PhabricatorUsersSearchField())
        ->setKey('ownerPHIDs')
        ->setAliases(array('users'))
        ->setLabel(pht('Users')),
      id(new PhabricatorSearchTextField())
        ->setLabel(pht('Current Channel'))
        ->setKey('currentChannel')
        ->setDescription(pht('Search for channel by PHID')),
      id(new PhabricatorSearchSelectField())
        ->setLabel('Status')
        ->setKey('status')
        ->setOptions(LobbyState::getStatusMap()),
      id(new PhabricatorSearchCheckboxesField())
        ->setKey('break')
        ->setOptions(array(
          'break' => pht('Show only those that in a break.'),
          )),
      id(new PhabricatorSearchThreeStateField())
        ->setLabel(pht('Working'))
        ->setKey('isWorking')
        ->setOptions(
          pht('(Show All)'),
          pht('Show Only Working'),
          pht('Hide Working Users'))
        ->setDescription(
          pht(
            'Pass true to find only working users, or false to omit '.
            'working users.')),
    );
  }

  protected function getURI($path) {
    return '/lobby/'.$path;
  }

  protected function getBuiltinQueryNames() {
    $names = array(
      'lobby' => pht('In Lobby'),
      'working' => pht('Working'),
      'break' => pht('In a Break'),
      'unavailable' => pht('Unavailable'),
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
      case 'lobby':
        return $query->setParameter('isWorking', 1)
          ->setParameter('status', LobbyState::STATUS_IN_LOBBY);
      case 'working':
        return $query->setParameter('isWorking', 1)
          ->setParameter('status', LobbyState::STATUS_IN_CHANNEL);
      case 'break':
        return $query->setParameter('isWorking', 1)
          ->setParameter('break', array('break'));
      case 'unavailable':
        return $query->setParameter('isWorking', 0);
    }

    return parent::buildSavedQueryFromBuiltin($query_key);
  }

  protected function getRequiredHandlePHIDsForResultList(
    array $states,
    PhabricatorSavedQuery $query) {
    return mpull($states, 'getOwnerPHID');
  }

  protected function renderResultList(
    array $states,
    PhabricatorSavedQuery $query,
    array $handles) {
    assert_instances_of($states, 'LobbyState');

    $viewer = $this->requireViewer();

    $list = id(new PHUIObjectItemListView())
      ->setUser($viewer);

    foreach ($states as $state) {
      $user = $state->getOwner();

      $ago = PhabricatorTime::getElapsedTimeAgo($state->getDateModified());

      $item = new PHUIObjectItemView();
      $item->setHeader($user->getFullName())
        ->setHref('/p/'.$user->getUserName().'/')
        ->addAttribute($ago)
        ->addAttribute($state->getCurrentDevice())
        ->setImageURI($user->getProfileImageURI());

      $item->addIcon($state->getStatusIcon(), $state->getStatusText());
      $list->addItem($item);
    }

    $result = new PhabricatorApplicationSearchResultView();
    $result->setObjectList($list);
    $result->setNoDataString(pht('No one found.'));

    $moderator_btn = id(new PHUIButtonView())
      ->setTag('a')
      ->setWorkflow(false)
      ->setIcon('fa-shield')
      ->setText('Manage Moderators')
      ->setHref('/lobby/moderators');
    $result->addAction($moderator_btn);

    return $result;
  }
}
