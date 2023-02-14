<?php

final class PerformanceWorklogSearchEngine
  extends PhabricatorApplicationSearchEngine {

  public function getResultTypeDescription() {
    return pht('State');
  }

  public function getApplicationClassName() {
    return 'PhabricatorPerformanceApplication';
  }

  public function newQuery() {
    return id(new LobbyStateQuery());
  }

  protected function buildCustomSearchFields() {
    $fields = array(
      id(new PhabricatorSearchStringListField())
        ->setLabel(pht('Usernames'))
        ->setKey('usernames')
        ->setAliases(array('username'))
        ->setDescription(pht('Find users by exact username.')),
      id(new PhabricatorSearchThreeStateField())
        ->setLabel(pht('Active'))
        ->setKey('isActive')
        ->setOptions(
          pht('(Show All)'),
          pht('Show Only Active Tags'),
          pht('Hide Active Tags'))
        ->setDescription(
          pht(
            'Pass true to find only active tags, or false to omit '.
            'active tags.')),
    );

    return $fields;
  }

  protected function getDefaultFieldOrder() {
    return array(
      '...',
      'createdStart',
      'createdEnd',
    );
  }

  protected function buildQueryFromParameters(array $map) {
    $query = $this->newQuery()->needOwner(true);

    $viewer = $this->requireViewer();

    // If the viewer can't browse the user directory, restrict the query to
    // just the user's own profile. This is a little bit silly, but serves to
    // restrict users from creating a dashboard panel which essentially just
    // contains a user directory anyway.
    $can_browse = PhabricatorPolicyFilter::hasCapability(
      $viewer,
      $this->getApplication(),
      PhabricatorPolicyCapability::CAN_VIEW);
    if (!$can_browse) {
      // $query->withOw(array($viewer->getPHID()));
    }

    $phids = array();
    if ($map['usernames']) {
      $existing_owner = id(new PhabricatorPeopleQuery())
                        ->withUsernames($map['usernames'])
                        ->setViewer(PhabricatorUser::getOmnipotentUser())
                        ->executeOne();

      if ($existing_owner) {
        $query->withOwnerPHIDs([$existing_owner->getPHID()]);
      }
    }

    if ($map['isActive'] !== null) {
      // Get only active ones

    } else {
      // Get all that already here
    }

    return $query;
  }

  protected function getURI($path) {
    return '/performance/worklog/'.$path;
  }

  protected function getBuiltinQueryNames() {
    $names = array(
      'active' => pht('Active'),
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
      case 'active':
        return $query;
    }

    return parent::buildSavedQueryFromBuiltin($query_key);
  }

  protected function renderResultList(
    array $states,
    PhabricatorSavedQuery $query,
    array $handles) {
      assert_instances_of($states, 'LobbyState');

      $viewer = $this->requireViewer();

      $can_manage = PhabricatorPolicyFilter::hasCapability(
        $viewer,
        $this->getApplication(),
        PerformanceManageCapability::CAPABILITY);

      $path = $this->getRequest()->getPath();
      $selected_period = $this->getRequest()->getStr('selectedPeriod', '');

      $start_date = 0;
      $end_date = PerformanceOption::getEndOfTheWeekEpoch();
      switch ($selected_period) {
        case PerformanceOption::PERIOD_7_DAYS:
          $start_date = PerformanceOption::getStartOfTheWeekEpoch();
          $end_date = PerformanceOption::getEndOfTheWeekEpoch();
          break;
        case PerformanceOption::PERIOD_14_DAYS:
          $end_date = PerformanceOption::getEndOfTheWeekEpoch();
          $start_date = PerformanceOption::getEpochFrom(14);
          break;
        case PerformanceOption::PERIOD_30_DAYS:
          $end_date = PerformanceOption::getEndOfTheWeekEpoch();
          $start_date = PerformanceOption::getEpochFrom(30);
          break;
        case PerformanceOption::PERIOD_90_DAYS:
          $end_date = PerformanceOption::getEndOfTheWeekEpoch();
          $start_date = PerformanceOption::getEpochFrom(90);
          break;
      }

      $phids = array_keys(mpull($states, null, 'getPHID'));
      $rows = array();


      $states_person = array();
      foreach ($states as $state) {
        $states_person[$state->getPHID()] = $state->getOwner()->getFullname();
      }

      if (count($states_person) > 1) {
        $table = id(new AphrontTableView($rows))
          ->setNoDataString(pht('Select one person.'))
          ->setHeaders(array())
          ->setColumnClasses(array());

        $notice = pht('Worklog');
        $table->setNotice($notice);

        $result = new PhabricatorApplicationSearchResultView();
        $result->setTable($table);

        return $this->setAction($result, $can_manage);
      }

      $table  = new LobbyStateTransaction();
      $conn_r = $table->establishConnection('r');
      $tasks_all = queryfx_all(
        $conn_r,
        'SELECT id,dateCreated,authorPHID,'.
        'objectPHID,oldValue,newValue FROM %T '.
        'WHERE objectPHID IN (%Ls) AND ('.
        '(oldValue LIKE %s) OR (newValue LIKE %s)) '.
        'ORDER BY dateCreated ASC',
        $table->getTableName(), array_keys($states_person),
        '%BIO %', '%BIO %');

      $rows = array();
      $previous_id = 0;
      foreach ($tasks_all as $task) {
        $id = $task['id'];
        $old_value = $task['oldValue'];
        $new_value = $task['newValue'];
        $date = phabricator_date($task['dateCreated'], $viewer);
        $datetime = phabricator_datetime($task['dateCreated'], $viewer);

        $end = '';
        $start = '';
        if (strpos($old_value, 'BIO') !== false) {
          $log = $old_value;
          $end = $datetime;
        } else {
          $log = $new_value;
          $start = $datetime;
        }

        if (!empty($end)) {
          $rows[$previous_id]['end'] = $end;
        } else {
          $rows[$id]['date'] = $date;
          $rows[$id]['start'] = $start;
          $rows[$id]['end'] = phabricator_datetime($task['dateCreated'] + 300,
            $viewer);
          $rows[$id]['task'] = str_replace('BIO ', '', $log);
        }

        $previous_id = $id;
      }


      foreach ($rows as $i => $row) {
        if (!array_key_exists('start', $row)) {
          unset($rows[$i]);
        }
      }

      array_multisort(array_keys($rows), SORT_ASC, SORT_NATURAL, $rows);

      $headers = array(
            pht('Date'),
            pht('Jam Kedatangan'),
            pht('Jam Kepulangan'),
            pht('Task'),
          );
      $column_classes = array(
            'pri',
            'right',
            'right',
            'right',
          );

      $table = id(new AphrontTableView($rows))
        ->setNoDataString(pht('No worklog match the query.'))
        ->setHeaders($headers)
        ->setColumnClasses($column_classes);

      $notice = pht('Worklog of %s', current(array_values($states_person)));
      $table->setNotice($notice);

      $result = new PhabricatorApplicationSearchResultView();
      $result->setTable($table);

      return $this->setAction($result, $can_manage);
  }

  protected function setAction(PhabricatorApplicationSearchResultView $result,
    $can_manage) {

    $path = $this->getRequest()->getPath();
    $selected = $this->getRequest()->getStr('selectedPeriod', '');
    $selected_option = idx(PerformanceOption::getPeriods(), $selected);

    $period_option = id(new PHUIButtonView())
      ->setTag('a')
      ->setIcon('fa-calendar ')
      ->setText(pht('Period: %s', $selected_option));

    $dropdown = id(new PhabricatorActionListView())
      ->setUser($this->requireViewer());

    foreach (PerformanceOption::getPeriods() as $key => $option) {
      $uri = $path.'?selectedPeriod='.$key;

      $dropdown->addAction(
        id(new PhabricatorActionView())
          ->setName($option)
          ->setHref($uri)
          ->setWorkflow(false));
    }

    $period_option->setDropdownMenu($dropdown);
    $result->addAction($period_option);

    return $result;
  }

  protected function newExportFields() {
    return array(
      id(new PhabricatorStringExportField())
        ->setKey('title')
        ->setLabel(pht('Title')),
    );
  }

  protected function newExportData(array $states) {
    $viewer = $this->requireViewer();

    $export = array();
    foreach ($states as $project) {
      $export[] = array(
        'title' => $project->getTitle(),
      );
    }

    return $export;
  }

}
