<?php

final class PerformanceProgressSearchEngine
  extends PhabricatorApplicationSearchEngine {

  public function getResultTypeDescription() {
    return pht('Tags');
  }

  public function getApplicationClassName() {
    return 'PhabricatorPerformanceApplication';
  }

  public function newQuery() {
    return id(new PhabricatorProjectQuery())
      ->needImages(true)
      ->needSlugs(true);
  }

  protected function buildCustomSearchFields() {
    $fields = array(
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
    $query = $this->newQuery()
                ->withIcons(['goal']);

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

    } else {
      // Get all that already here
    }

    return $query;
  }

  protected function getURI($path) {
    return '/performance/progress/'.$path;
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
    array $projects,
    PhabricatorSavedQuery $query,
    array $handles) {
      assert_instances_of($projects, 'PhabricatorProject');

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

      $phids = array_keys(mpull($projects, null, 'getPHID'));
      $rows = array();

      $edge_query = id(new PhabricatorEdgeQuery())
        ->withSourcePHIDs($phids);
      $edge_query->execute();

      $project_tasks = array();
      $active_tasks_phids = array();
      foreach ($projects as $project) {
        $task_phids = $edge_query->getDestinationPHIDs(
          array($project->getPHID()));
        $active_tasks_phids =
          array_unique(array_merge($active_tasks_phids, $task_phids));
        $project_tasks[$project->getPHID()] = $task_phids;
      }

      $table  = new ManiphestTask();
      $conn_r = $table->establishConnection('r');

      $stats = array();
      foreach ($phids as $phid) {
        $stats[$phid]['tasks'] = '';
        $stats[$phid]['all_tasks'] = 0;
        $stats[$phid]['commitment'] = 0;
        $stats[$phid]['completed'] = 0;

        // Only count active phids
        $project_task_phids = $project_tasks[$phid];
        if (count($project_task_phids) > 0) {
          $tasks_all = queryfx_all(
            $conn_r,
            'SELECT SUM(points) as all_tasks,phid FROM %T '.
            'WHERE phid IN (%Ls) GROUP BY phid',
            $table->getTableName(), $project_tasks[$phid]);

          $tasks_commitment = queryfx_all(
            $conn_r,
            'SELECT SUM(points) as commitment_points, phid FROM %T '.
            'WHERE phid IN (%Ls) '.
            'AND status=%s GROUP BY phid',
            $table->getTableName(), $project_tasks[$phid], 'open');
          $tasks_resolved = queryfx_all(
            $conn_r,
            'SELECT SUM(points) as completed_points, phid FROM %T '.
            'WHERE phid IN (%Ls) '.
            'AND status=%s GROUP BY phid',
            $table->getTableName(), $project_tasks[$phid], 'resolved');

          foreach ($tasks_all as $task_all) {
              $stats[$phid]['all_tasks'] += (int)$task_all['all_tasks'];
          }

          foreach ($tasks_commitment as $task_commitment) {
              $stats[$phid]['commitment'] +=
                (int)$task_commitment['commitment_points'];
          }

          foreach ($tasks_resolved as $task_resolved) {
              $stats[$phid]['completed'] +=
                (int)$task_resolved['completed_points'];
          }
        }
      }


      $rows = array();
      foreach ($projects as $id => $project) {
        $phid = $project->getPHID();
        $committed = $stats[$phid]['commitment'];

        $index = ((string)$project->getName()).'-'.((string)$id);


        $engine = PhabricatorMarkupEngine::getEngine()
                  ->setConfig('viewer', PhabricatorUser::getOmnipotentUser());
        $tickets = $engine->markupText($stats[$phid]['tasks']);

        $percentage = 0;
        if ($stats[$phid]['all_tasks'] > 0
            && $stats[$phid]['completed'] > 0) {
            $percentage =
              round(
                ($stats[$phid]['completed'] / $stats[$phid]['all_tasks'])
                  * 100);
        }

        $rows[$index] = array(
          $project->getName(),
          $stats[$phid]['all_tasks'],
          $committed,
          $stats[$phid]['completed'],
          pht('%d', $percentage).'%',
        );
      }
      array_multisort(array_keys($rows), SORT_ASC, SORT_NATURAL, $rows);

      $headers = array(
            pht('Goal Name'),
            pht('SP Total'),
            pht('SP Open'),
            pht('SP Completed'),
            pht('Completion Percentage'),
          );
      $column_classes = array(
            'pri',
            'right',
            'right',
            'right',
            'right',
          );

      $table = id(new AphrontTableView($rows))
        ->setNoDataString(pht('No goals match the query.'))
        ->setHeaders($headers)
        ->setColumnClasses($column_classes);

      $notice = pht('Goal Progress');
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

  protected function newExportData(array $projects) {
    $viewer = $this->requireViewer();

    $export = array();
    foreach ($projects as $project) {
      $export[] = array(
        'title' => $project->getTitle(),
      );
    }

    return $export;
  }

}
