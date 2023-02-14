<?php

final class PerformancePipSearchEngine
  extends PhabricatorApplicationSearchEngine {

  public function getResultTypeDescription() {
    return pht('Users');
  }

  public function getApplicationClassName() {
    return 'PhabricatorPerformanceApplication';
  }

  public function newQuery() {
    return id(new PhabricatorPeopleQuery())
      ->needPrimaryEmail(true)
      ->needProfileImage(true);
  }

  protected function buildCustomSearchFields() {
    $fields = array(
      id(new PhabricatorSearchThreeStateField())
        ->setLabel(pht('Active'))
        ->setKey('isActive')
        ->setOptions(
          pht('(Show All)'),
          pht('Show Only Active Users'),
          pht('Hide Active Users'))
        ->setDescription(
          pht(
            'Pass true to find only active users, or false to omit '.
            'active users.')),
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
    $query = $this->newQuery();

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
      $query->withPHIDs(array($viewer->getPHID()));
    }

    $phids = array();
    if ($map['isActive'] !== null) {
      // Get only active ones
      $all = id(new PerformancePipQuery())
                      ->withIsActive($map['isActive'])
                      ->setViewer(PhabricatorUser::getOmnipotentUser())
                      ->execute();
      $phids = mpull($all,'getTargetPHID');
    } else {
      // Get all that already here
      $all = id(new PerformancePipQuery())
                      ->setViewer(PhabricatorUser::getOmnipotentUser())
                      ->execute();
      $phids = mpull($all,'getTargetPHID');
    }

    if (empty($phids)) {
      $phids = array('PHID-0');
    }

    $query->withPHIDs($phids);

    // Get only active ones
    $wl = id(new PerformanceWhitelistQuery())
                    ->withIsActive(true)
                    ->setViewer(PhabricatorUser::getOmnipotentUser())
                    ->execute();
    $wl_phids = mpull($wl,'getTargetPHID');
    if (!empty($wl_phids)) {
      $query->withoutPHIDs($wl_phids);
    }

    return $query;
  }

  protected function getURI($path) {
    return '/performance/pip/'.$path;
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
        return $query
          ->setParameter('isActive', true);
    }

    return parent::buildSavedQueryFromBuiltin($query_key);
  }

  protected function renderResultList(
    array $users,
    PhabricatorSavedQuery $query,
    array $handles) {
      assert_instances_of($users, 'PhabricatorUser');

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

      $phids = array_keys(mpull($users, null, 'getPHID'));

      $table  = new ManiphestTask();
      $conn_r = $table->establishConnection('r');

      // Only count active phids
      $trans  = new ManiphestTransaction();
      $active_tasks = queryfx_all(
        $conn_r,
        'SELECT t.id, trans.objectPHID, trans.newValue FROM %T trans '.
        'LEFT JOIN %T t ON trans.objectPHID = t.phid '.
        'WHERE trans.transactionType=%s AND '.
        'trans.dateCreated BETWEEN %d AND %d',
        $trans->getTableName(),
        id(new ManiphestTask())->getTableName(),
        'reassign',
        $start_date,
        $end_date);
      $active_ids = array_column($active_tasks, 'id');
      $active_phids = array_column($active_tasks, 'objectPHID');
      $active_users_phids = array_column($active_tasks, 'newValue');
      if (empty($active_phids)) {
        $active_phids = array('PHID-0');
        $active_users_phids = array('PHID-0');
      }
      array_walk($active_users_phids, function (&$val) {
        $val = str_replace('"', '', $val);
      });
      $assignee_items = array_combine($active_ids, $active_users_phids);

      $tasks_all = queryfx_all(
        $conn_r,
        'SELECT COUNT(id) as all_tasks, ownerPHID FROM %T '.
        'WHERE phid IN (%Ls) GROUP BY ownerPHID',
        $table->getTableName(), $active_phids);

      $tasks_commitment = queryfx_all(
        $conn_r,
        'SELECT SUM(points) as commitment_points, ownerPHID FROM %T '.
        'WHERE phid IN (%Ls) GROUP BY ownerPHID',
        $table->getTableName(), $active_phids);

      $tasks_resolved = queryfx_all(
        $conn_r,
        'SELECT SUM(points) as completed_points, ownerPHID FROM %T '.
        'WHERE phid IN (%Ls) '.
        'AND status=%s GROUP BY ownerPHID',
        $table->getTableName(), $active_phids, 'resolved');

      $all = id(new PerformancePipQuery())
                      ->setViewer(PhabricatorUser::getOmnipotentUser())
                      ->execute();
      $pip_all = mpull($all,null,'getTargetPHID');
      $stats = array();
      foreach($phids as $phid) {
        $stats[$phid]['tasks'] = '';
        $stats[$phid]['all_tasks'] = 0;
        $stats[$phid]['commitment'] = 0;
        $stats[$phid]['completed'] = 0;

        foreach($tasks_all as $task_all) {
          if ($task_all['ownerPHID'] == $phid) {
            $stats[$phid]['all_tasks'] = (int)$task_all['all_tasks'];
          }
        }

        foreach($tasks_commitment as $task_commitment) {
          if ($task_commitment['ownerPHID'] == $phid) {
            $stats[$phid]['commitment'] = (int)$task_commitment['commitment_points'];
          }
        }

        foreach($tasks_resolved as $task_resolved) {
          if ($task_resolved['ownerPHID'] == $phid) {
            $stats[$phid]['completed'] = (int)$task_resolved['completed_points'];
          }
        }

        if (in_array($phid, $assignee_items)) {
          $assigned_ids = array_keys(array_filter($assignee_items, function ($v) use ($phid) {
            return $v == $phid;
          }));
          $task_monograms = array();
          foreach ($assigned_ids as $assigned_id) {
            $task_monograms[] = 'T'.$assigned_id.'';
          }

          $displayed_tasks = array_slice($task_monograms,0,10);
          $more_tasks = array_slice($task_monograms,10);

          $stats[$phid]['tasks'] = implode(' ', $displayed_tasks);

          if (count($more_tasks) > 0){
            $stats[$phid]['tasks'] .= ' and '. count($more_tasks).' more';
          }
        }
      }


      $rows = array();
      foreach ($users as $id => $user) {
        $phid = $user->getPHID();
        $committed = $stats[$phid]['commitment'];

        $index = ((string)$committed).'-'.((string)$id);


        $engine = PhabricatorMarkupEngine::getEngine()
                  ->setConfig('viewer', PhabricatorUser::getOmnipotentUser());
        $tickets = $engine->markupText($stats[$phid]['tasks']);

        $rows[$index] = array(
          $id,
          phutil_tag('a',
            array('href' => '/p/'.$user->getUserName()),
            array($user->getRealName())),
          $stats[$phid]['all_tasks'],
          $committed,
          $stats[$phid]['completed'],
          $tickets,
        );

        if ($can_manage) {
          $active = false;
          if (array_key_exists($phid, $pip_all)) {
            $current_pip = $pip_all[$phid];
            $active = $current_pip->getIsActive();
          }
          $icon = $active ? 'fa-check' : 'fa-sign-in';
          $color = $active ? 'green' : 'blue';
          $tooltip = $active
                    ? 'Pass Improvement Period'
                    : 'Re-add to Improvement Period';

          $action = id(new PHUIButtonView())
          ->setTag('a')
          ->setWorkflow(true)
          ->setIcon($icon)
          ->setColor($color)
          ->setTooltip($tooltip)
          ->setHref('/performance/pip/toggle/'.$id.'/?via=pip');
          $rows[$index][] = $action;
        }
      }
      array_multisort(array_keys($rows), SORT_DESC, SORT_NATURAL, $rows);

      $headers = array(
            pht('ID'),
            pht('User'),
            pht('# Tickets'),
            pht('SP Commitment'),
            pht('SP Completed'),
            pht('Tickets Reference'),
          );
      $column_classes = array(
            'pri',
            ' ',
            'right',
            'right',
            'right',
            ' ',
          );

      if ($can_manage) {
        $headers[] = pht('Action');
        $column_classes[] = 'right';
      }

      $table = id(new AphrontTableView($rows))
        ->setNoDataString(pht('No users match the query.'))
        ->setHeaders($headers)
        ->setColumnClasses($column_classes);

      $notice = pht('Story Point performance');
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
        ->setKey('realName')
        ->setLabel(pht('Real Name')),
    );
  }

  protected function newExportData(array $users) {
    $viewer = $this->requireViewer();

    $export = array();
    foreach ($users as $user) {
      $export[] = array(
        'realName' => $user->getRealName(),
      );
    }

    return $export;
  }

}
