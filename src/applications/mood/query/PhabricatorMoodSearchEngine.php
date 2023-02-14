<?php

final class PhabricatorMoodSearchEngine
  extends PhabricatorApplicationSearchEngine {

  public function getResultTypeDescription() {
    return pht('Moods');
  }

  public function getApplicationClassName() {
    return 'PhabricatorMoodApplication';
  }

  public function newQuery() {
    return new PhabricatorMoodQuery();
  }

  protected function buildQueryFromParameters(array $map) {
    $query = $this->newQuery();

    if ($this->requireViewer()->getIsAdmin()) {
      $query->withUserPHIDs($map['userPHIDs']);
    } else {
      $query->withUserPHIDs(array($this->requireViewer()->getPHID()));
    }

    if ($map['mood']) {
      $query->withMood(array($map['mood']));
    }

    if ($map['startDate']) {
      $query->withStartDate($map['startDate']);
    }

    if ($map['endDate']) {
      $query->withEndDate($map['endDate']);
    }

    if ($map['isForDev'] !== null) {
      $query->withIsForDev($map['isForDev']);
    }

    return $query;
  }

  protected function buildCustomSearchFields() {
    return array(
      id(new PhabricatorUsersSearchField())
        ->setKey('userPHIDs')
        ->setAliases(array('user', 'users'))
        ->setLabel(pht('Users'))
        ->setDescription(
          pht('Search for users moods with specific user PHIDs.')),
      id(new PhabricatorSearchTextField())
        ->setLabel(pht('Mood'))
        ->setKey('mood')
        ->setDescription(
          pht('Find mood contain a substring.')),
      id(new PhabricatorSearchDateField())
        ->setLabel(pht('Mood After'))
        ->setKey('startDate')
        ->setDescription(
          pht('Find mood after a given time.')),
      id(new PhabricatorSearchDateField())
        ->setLabel(pht('Mood Before'))
        ->setKey('endDate')
        ->setDescription(
          pht('Find mood before a given time.')),
      id(new PhabricatorSearchThreeStateField())
        ->setLabel(pht('Development'))
        ->setKey('isForDev')
        ->setDescription(
          pht(
            'Pass yes to find project only for '.
            'Development.'))
        ->setOptions(
          pht('(Show All)'),
          pht('Show Only For Development'),
          pht('Hide Only Development')),
    );
  }

  protected function getURI($path) {
    return '/mood/'.$path;
  }

  protected function getBuiltinQueryNames() {
    $names = array(
      'all' => pht('All Moods'),
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
    array $moods,
    PhabricatorSavedQuery $query) {
    return mpull($moods, 'getUserPHID');
  }

  protected function renderResultList(
    array $moods,
    PhabricatorSavedQuery $query,
    array $handles) {

    assert_instances_of($moods, 'PhabricatorMood');

    $viewer = $this->requireViewer();

    $list = new PHUIObjectItemListView();
    foreach ($moods as $mood) {
      $item = id(new PHUIObjectItemView())
        ->setUser($viewer)
        ->setObject($mood)
        ->setObjectName($mood->getMood())
        ->addByline(
          pht(
            'Created by %s',
            $handles[$mood->getUserPHID()]->renderLink()));

      $item->addAttribute(
        pht('Submit on %s', phabricator_datetime(
          $mood->getStartDate(), $viewer)));
      $list->addItem($item);
    }

    $result = new PhabricatorApplicationSearchResultView();
    $result->setObjectList($list);
    $result->setNoDataString(pht('No user mood found.'));

    return $result;
  }
}
