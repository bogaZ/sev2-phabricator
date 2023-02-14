<?php

final class SuiteProjectSearchEngine
  extends PhabricatorApplicationSearchEngine {

  public function getResultTypeDescription() {
    return pht('Projects');
  }

  public function getApplicationClassName() {
    return 'PhabricatorSuiteApplication';
  }

  public function newQuery() {
    return id(new PhabricatorProjectQuery())
      ->needImages(true)
      ->needMembers(true)
      ->needWatchers(true)
      ->needTickets(true);
  }

  protected function buildCustomSearchFields() {
    $subtype_map = id(new PhabricatorProject())->newEditEngineSubtypeMap();
    $hide_subtypes = ($subtype_map->getCount() == 1);

    return array(
      id(new PhabricatorSearchTextField())
        ->setLabel(pht('Name'))
        ->setKey('name')
        ->setDescription(
          pht(
            '(Deprecated.) Search for projects with a given name or '.
            'hashtag using tokenizer/datasource query matching rules. This '.
            'is deprecated in favor of the more powerful "query" '.
            'constraint.')),
      id(new PhabricatorSearchStringListField())
        ->setLabel(pht('Slugs'))
        ->setIsHidden(true)
        ->setKey('slugs')
        ->setDescription(
          pht(
            'Search for projects with particular slugs. (Slugs are the same '.
            'as project hashtags.)')),
      id(new PhabricatorUsersSearchField())
        ->setLabel(pht('Members'))
        ->setKey('memberPHIDs')
        ->setConduitKey('members')
        ->setAliases(array('member', 'members')),
      id(new PhabricatorUsersSearchField())
        ->setLabel(pht('Watchers'))
        ->setKey('watcherPHIDs')
        ->setConduitKey('watchers')
        ->setAliases(array('watcher', 'watchers')),
      id(new PhabricatorSearchSelectField())
        ->setLabel(pht('Status'))
        ->setKey('status')
        ->setOptions($this->getStatusOptions()),
      id(new PhabricatorSearchThreeStateField())
        ->setLabel(pht('RSP Enabled'))
        ->setKey('isForRsp')
        ->setOptions(
          pht('(Show All)'),
          pht('Show Only RSP Enabled'),
          pht('Hide RSP Enabled'))
        ->setDescription(
          pht(
            'Pass true to find only RSP enabled, or false to omit '.
            'RSP enabled projects.')),
      id(new PhabricatorSearchCheckboxesField())
        ->setLabel(pht('Icons'))
        ->setKey('icons')
        ->setOptions($this->getIconOptions()),
      id(new PhabricatorSearchThreeStateField())
        ->setLabel(pht('Milestones'))
        ->setKey('isMilestone')
        ->setOptions(
          pht('(Show All)'),
          pht('Show Only Milestones'),
          pht('Hide Milestones'))
        ->setDescription(
          pht(
            'Pass true to find only milestones, or false to omit '.
            'milestones.')),
      id(new PhabricatorSearchThreeStateField())
        ->setLabel(pht('Root Projects'))
        ->setKey('isRoot')
        ->setOptions(
          pht('(Show All)'),
          pht('Show Only Root Projects'),
          pht('Hide Root Projects'))
        ->setDescription(
          pht(
            'Pass true to find only root projects, or false to omit '.
            'root projects.')),
      id(new PhabricatorPHIDsSearchField())
        ->setLabel(pht('Parent Projects'))
        ->setKey('parentPHIDs')
        ->setConduitKey('parents')
        ->setAliases(array('parent', 'parents', 'parentPHID'))
        ->setDescription(pht('Find direct subprojects of specified parents.')),
      id(new PhabricatorPHIDsSearchField())
        ->setLabel(pht('Ancestor Projects'))
        ->setKey('ancestorPHIDs')
        ->setConduitKey('ancestors')
        ->setAliases(array('ancestor', 'ancestors', 'ancestorPHID'))
        ->setDescription(
          pht('Find all subprojects beneath specified ancestors.')),
    );
  }


  protected function buildQueryFromParameters(array $map) {
    $query = $this->newQuery();

    if (strlen($map['name'])) {
      $tokens = PhabricatorTypeaheadDatasource::tokenizeString($map['name']);
      $query->withNameTokens($tokens);
    }

    if ($map['slugs']) {
      $query->withSlugs($map['slugs']);
    }

    if ($map['memberPHIDs']) {
      $query->withMemberPHIDs($map['memberPHIDs']);
    }

    if ($map['watcherPHIDs']) {
      $query->withWatcherPHIDs($map['watcherPHIDs']);
    }

    if ($map['status']) {
      $status = idx($this->getStatusValues(), $map['status']);
      if ($status) {
        $query->withStatus($status);
      }
    }

    if ($map['icons']) {
      $query->withIcons($map['icons']);
    }

    if ($map['isForRsp'] !== null) {
      $query->withIsForRsp($map['isForRsp']);
    }

    if ($map['isMilestone'] !== null) {
      $query->withIsMilestone($map['isMilestone']);
    }

    $min_depth = null;
    $max_depth = null;

    if ($map['isRoot'] !== null) {
      if ($map['isRoot']) {
        if ($max_depth === null) {
          $max_depth = 0;
        } else {
          $max_depth = min(0, $max_depth);
        }

        $query->withDepthBetween(null, 0);
      } else {
        if ($min_depth === null) {
          $min_depth = 1;
        } else {
          $min_depth = max($min_depth, 1);
        }
      }
    }

    if ($map['parentPHIDs']) {
      $query->withParentProjectPHIDs($map['parentPHIDs']);
    }

    if ($map['ancestorPHIDs']) {
      $query->withAncestorProjectPHIDs($map['ancestorPHIDs']);
    }

    $query->needRspSpec(true);

    return $query;
  }

  protected function getURI($path) {
    return '/suite/projects/'.$path;
  }

  protected function getBuiltinQueryNames() {
    $names = array();

    $names['rsp'] = pht('RSP Enabled');
    $names['all'] = pht('All');

    return $names;
  }

  public function buildSavedQueryFromBuiltin($query_key) {
    $query = $this->newSavedQuery();
    $query->setQueryKey($query_key);

    $viewer_phid = $this->requireViewer()->getPHID();

    // By default, do not show milestones in the list view.
    $query->setParameter('isMilestone', false);

    switch ($query_key) {
      case 'all':
        return $query
          ->setParameter('status', 'active')
          ->setParameter('isRoot', false)
          ->setParameter('isMilestone', false)
          ->setParameter('icons', array('project'));
      case 'rsp':
        return $query
          ->setParameter('status', 'active')
          ->setParameter('isForRsp', true)
          ->setParameter('isRoot', false)
          ->setParameter('isMilestone', false)
          ->setParameter('icons', array('project'));
    }

    return parent::buildSavedQueryFromBuiltin($query_key);
  }

  private function getStatusOptions() {
    return array(
      'active'   => pht('Show Only Active Projects'),
      'archived' => pht('Show Only Archived Projects'),
      'all'      => pht('Show All Projects'),
    );
  }

  private function getStatusValues() {
    return array(
      'active'   => PhabricatorProjectQuery::STATUS_ACTIVE,
      'archived' => PhabricatorProjectQuery::STATUS_ARCHIVED,
      'all'      => PhabricatorProjectQuery::STATUS_ANY,
    );
  }

  private function getIconOptions() {
    $options = array();

    $set = new PhabricatorProjectIconSet();
    foreach ($set->getIcons() as $icon) {
      if ($icon->getIsDisabled()) {
        continue;
      }

      $options[$icon->getKey()] = array(
        id(new PHUIIconView())
          ->setIcon($icon->getIcon()),
        ' ',
        $icon->getLabel(),
      );
    }

    return $options;
  }

  private function getColorOptions() {
    $options = array();

    foreach (PhabricatorProjectIconSet::getColorMap() as $color => $name) {
      $options[$color] = array(
        id(new PHUITagView())
          ->setType(PHUITagView::TYPE_SHADE)
          ->setColor($color)
          ->setName($name),
      );
    }

    return $options;
  }

  protected function renderResultList(
    array $projects,
    PhabricatorSavedQuery $query,
    array $handles) {
    assert_instances_of($projects, 'PhabricatorProject');
    $viewer = $this->requireViewer();

    $list = id(new SuiteProjectListView())
      ->setBaseUri($this->getApplicationURI('projects'))
      ->setUser($viewer)
      ->setProjects($projects)
      ->setShowWatching(true)
      ->setShowMember(true)
      ->renderList();

    return id(new PhabricatorApplicationSearchResultView())
      ->setObjectList($list)
      ->setNoDataString(pht('No projects found.'));
  }
}
