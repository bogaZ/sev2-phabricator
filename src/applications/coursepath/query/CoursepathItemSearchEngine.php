<?php

final class CoursepathItemSearchEngine
  extends PhabricatorApplicationSearchEngine {

  public function getResultTypeDescription() {
    return pht('Course Paths');
  }

  public function getApplicationClassName() {
    return 'PhabricatorCoursepathApplication';
  }

  public function newQuery() {
    return new CoursepathItemQuery();
  }

  protected function buildCustomSearchFields() {
    return array(
      id(new PhabricatorSearchTextField())
        ->setLabel(pht('Name Contains'))
        ->setKey('name')
        ->setDescription(pht('Search for course path by name substring.')),
      id(new PhabricatorSearchTextField())
        ->setLabel(pht('Slug'))
        ->setKey('slug')
        ->setDescription(pht('Search for course path slug.')),
      id(new PhabricatorSearchCheckboxesField())
        ->setKey('statuses')
        ->setLabel(pht('Status'))
        ->setOptions(
          id(new CoursepathItem())
            ->getStatusNameMap()),
      id(new PhabricatorUsersSearchField())
        ->setLabel(pht('Registrar'))
        ->setKey('registrarPHIDs')
        ->setAliases(array('registrar', 'registrars'))
        ->setDescription(
          pht('Search for course path enrolled by any given user.')),
    );
  }

  protected function buildQueryFromParameters(array $map) {
    $query = $this->newQuery();

    if ($map['statuses']) {
      $query->withStatuses($map['statuses']);
    }

    if ($map['name'] !== null) {
      $query->withNameNgrams($map['name']);
    }

    if ($map['registrarPHIDs'] !== null) {
      $query->withRegistrarPHIDs($map['registrarPHIDs']);
    }

    if (!empty($map['slug'])) {
      $query->withSlug($map['slug']);
    }

    return $query;
  }

  protected function getURI($path) {
    return '/coursepath/item/'.$path;
  }

  protected function getBuiltinQueryNames() {
    $names = array();

    $names['open'] = pht('Active Paths');
    $names['all'] = pht('All Paths');

    return $names;
  }

  public function buildSavedQueryFromBuiltin($query_key) {
    $query = $this->newSavedQuery();
    $query->setQueryKey($query_key);

    switch ($query_key) {
      case 'all':
        return $query;
      case 'open':
        return $query->setParameter(
          'statuses',
          array(
            CoursepathItem::STATUS_ACTIVE,
          ));
    }

    return parent::buildSavedQueryFromBuiltin($query_key);
  }

  protected function getRequiredHandlePHIDsForResultList(
    array $items,
    PhabricatorSavedQuery $query) {

    $phids = array();

    return $phids;
  }

  protected function renderResultList(
    array $items,
    PhabricatorSavedQuery $query,
    array $handles) {
    assert_instances_of($items, 'CoursepathItem');

    $viewer = $this->requireViewer();

    $list = id(new PHUIObjectItemListView());
    foreach ($items as $path) {
      $item = id(new PHUIObjectItemView())
        ->setHeader($path->getName())
        ->setIcon('fa fa-road')
        ->setHref('/coursepath/item/view/'.$path->getID().'/')
        ->setSubHead($path->getDescription());

      if ($path->isArchived()) {
        $item->setDisabled(true);
        $item->addIcon('fa-ban', pht('Archived'));
      }

      $list->addItem($item);
    }

    $result = new PhabricatorApplicationSearchResultView();
    $result->setObjectList($list);
    $result->setNoDataString(pht('No course path found.'));

    return $result;

  }

  protected function getNewUserBody() {
    $create_button = id(new PHUIButtonView())
      ->setTag('a')
      ->setText(pht('Create a Course Path'))
      ->setHref('/coursepath/item/edit/form/default')
      ->setColor(PHUIButtonView::GREEN);

    $icon = $this->getApplication()->getIcon();
    $app_name =  $this->getApplication()->getName();
    $view = id(new PHUIBigInfoView())
      ->setIcon($icon)
      ->setTitle(pht('Welcome to %s', $app_name))
      ->setDescription(
        pht('Course path let you setup a learning path for users '.
          'throughout Suite.'))
      ->addAction($create_button);

      return $view;
  }

}
