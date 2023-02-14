<?php

final class LobbyStickitSearchEngine
  extends PhabricatorApplicationSearchEngine {

  public function getResultTypeDescription() {
    return pht('Lobby Stickit');
  }

  public function getApplicationClassName() {
    return 'PhabricatorLobbyApplication';
  }

  public function newQuery() {
    return id(new LobbyStickitQuery());
  }

  protected function buildQueryFromParameters(array $map) {
    $query = $this->newQuery();

    $query->withoutNoteType('goals');

    if ($map['ownerPHIDs']) {
      $query->withOwnerPHIDs($map['ownerPHIDs']);
    }

    return $query;
  }

  protected function buildCustomSearchFields() {
    return array(
      id(new PhabricatorUsersSearchField())
        ->setKey('ownerPHIDs')
        ->setAliases(array('users'))
        ->setLabel(pht('Users')),

    );
  }

  protected function getURI($path) {
    return '/lobby/stickit/'.$path;
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
    array $items,
    PhabricatorSavedQuery $query) {
    return mpull($items, 'getOwnerPHID');
  }

  protected function renderResultList(
    array $items,
    PhabricatorSavedQuery $query,
    array $handles) {
    assert_instances_of($items, 'LobbyStickit');

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

    foreach ($items as $stickit) {
      $item = new PHUIObjectItemView();
      $item->setHeader($stickit->getTitle())
        ->setHref($stickit->getViewURI())
        ->addAttribute(phabricator_datetime($stickit->getDateModified(),
          $viewer));

      if ($can_manage) {
        $id = $stickit->getID();
        $item->addAction(
          id(new PHUIListItemView())
            ->setIcon('fa-edit')
            ->setName(pht('Edit'))
            ->setHref($stickit->getEditURI()));
      }

      $list->addItem($item);
    }

    $result = new PhabricatorApplicationSearchResultView();
    $result->setObjectList($list);
    $result->setNoDataString(pht('No stickit found.'));

    return $result;
  }

  protected function getNewUserBody() {
    $create_button = id(new PHUIButtonView())
      ->setTag('a')
      ->setText(pht('Add a stickit'))
      ->setHref('/lobby/stickit/edit/form/default')
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
