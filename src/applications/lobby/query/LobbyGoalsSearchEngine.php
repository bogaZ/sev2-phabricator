<?php

final class LobbyGoalsSearchEngine
  extends PhabricatorApplicationSearchEngine {

  public function getResultTypeDescription() {
    return pht('Lobby Goals');
  }

  public function getApplicationClassName() {
    return 'PhabricatorLobbyApplication';
  }

  public function newQuery() {
    return id(new LobbyStickitQuery());
  }

  protected function buildQueryFromParameters(array $map) {
    $query = $this->newQuery();
    $query->withNoteType('goals');

    if ($map['tags']) {
      $query->withProjectGoalsPHIDs($map['tags']);
    }
    if (!is_null($map['archive'])
          && $map['archive'] != 2) {
      $query->isArchived($map['archive']);
    }
    if ($map['ownerPHIDs']) {
      $query->withOwnerPHIDs($map['ownerPHIDs']);
    }
    if ($map['createdStart']) {
      $query->withDateCreatedAfter($map['createdStart']);
    }
    if ($map['createdEnd']) {
      $query->withDateCreatedBefore($map['createdEnd']);
    }

    return $query;
  }

  protected function buildCustomSearchFields() {
    $options = [
      'None' => 'None',
      'Project' => 'Project',
    ];
    $statuses = [
      0 => pht('Incomplete'),
      1 => pht('Complete'),
      2 => pht('All'),
    ];

    return array(
      id(new PhabricatorUsersSearchField())
        ->setKey('ownerPHIDs')
        ->setAliases(array('users'))
        ->setLabel(pht('Users')),
      id(new PhabricatorProjectSearchField())
        ->setKey('tags')
        ->setAliases(array('project', 'projects', 'tag', 'tags'))
        ->setLabel(pht('Tags'))
        ->setEdgeType(pht('all_project'))
        ->setDescription(
          pht('Search for objects tagged with given projects.')),
      id(new PhabricatorSearchSelectField())
        ->setKey('archive')
        ->setLabel(pht('Statuses'))
        ->setOptions($statuses)
        ->setDefault(2),
      id(new PhabricatorSearchSelectField())
        ->setLabel('Group By')
        ->setKey('group')
        ->setOptions($options)
        ->setDefault('Project'),
      id(new PhabricatorSearchDateField())
        ->setLabel(pht('Created After'))
        ->setKey('createdStart'),
      id(new PhabricatorSearchDateField())
        ->setLabel(pht('Created Before'))
        ->setKey('createdEnd'),
    );
  }

  protected function getURI($path) {
    return '/lobby/goals/'.$path;
  }

  protected function getBuiltinQueryNames() {
    $names = array(
      'default' => pht('Daily'),
      'weekly' => pht('Weekly'),
      'monthly' => pht('Monthly'),
      'all' => pht('All'),
    );
    return $names;
  }

  public function buildSavedQueryFromBuiltin($query_key) {
    $query = $this->newSavedQuery();
    $query->setQueryKey($query_key);

    switch ($query_key) {
      case 'default':
        return $query
        ->setParameter('createdStart', 'today')
        ->setParameter('group', 'Project');
      case 'weekly':
        return $query
        ->setParameter('createdStart', '1 week ago')
        ->setParameter('group', 'Project');
      case 'monthly':
        return $query
        ->setParameter('createdStart', '1 month ago')
        ->setParameter('group', 'Project');
      case 'all':
        return $query
        ->setParameter('createdStart', null)
        ->setParameter('group', 'Project');
    }

    return parent::buildSavedQueryFromBuiltin($query_key);
  }

  private function filterTask(LobbyStickit $item) {
    $limit = 10;
    $count = 0;
    $views = id(new PHUIObjectItemView())
      ->setUser($this->getRequest()->getUser());

    $task_phids = PhabricatorEdgeQuery::loadDestinationPHIDs(
      $item->getPHID(),
      LobbyGoalsHasManiphestEdgeType::EDGECONST);

    if (!empty($task_phids)) {
      $tasks = id(new ManiphestTaskQuery())
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->withPHIDs($task_phids)
            ->execute();

      foreach ($tasks as $key => $task) {
        $count += 1;
        if ($count > $limit) {
          $task_tag = id(new PHUITagView())
          ->setName(pht('...and %d task more', (count($tasks) - $limit)))
          ->setHref($item->getViewURI())
          ->setColor(PHUITagView::COLOR_BLUE)
          ->setType(PHUITagView::TYPE_SHADE);

          $views->addAttribute($task_tag);
          break;
        } else {
          $task_tag = id(new PHUITagView())
          ->setIcon($this->setIconTask($task->getStatus()))
          ->setName(pht('T%s', $task->getID()))
          ->setColor($this->setColorTask($task->getStatus()))
          ->setType(PHUITagView::TYPE_SHADE)
          ->setHref('/T'.$task->getID());
          $views->addAttribute($task_tag);
        }
      }
    }
    return $views;
  }

  private function setIconTask($status) {
    switch ($status) {
      case 'invalid':
        return 'fa-ban';
      case 'resolved':
        return 'fa-check-circle';
      case 'wontfix':
        return 'fa-minus-circle';
      case 'duplicate':
        return 'fa-files-o';
      default:
        return 'fa-anchor';
    }
  }

  private function setColorTask($status) {
    if ($status === 'open') {
      return PHUITagView::COLOR_BLUE;
    } else {
      return PHUITagView::COLOR_DISABLED;
    }
  }

  protected function getRequiredHandlePHIDsForResultList(
    array $items,
    PhabricatorSavedQuery $query) {

    return mpull($items, 'getOwnerPHID');
  }
  // How this method work is, find relationship between project and conpherence
  // after that need to find relation between conpherence with goals

  protected function renderResultList(
    array $items,
    PhabricatorSavedQuery $query,
    array $handles) {
    assert_instances_of($items, 'LobbyStickit');
    $viewer = $this->requireViewer();
    $icon = ManiphestTaskStatus::getStatusIcon('resolved');

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
    $tags = [];
    $group = $query->getParameters();
    if ($group && isset($group['tags']) ) {
      $tags = $group['tags'];
      $group = $group['group'];
    } else if ($group ) {
      $group = $query->getParameterMap();
      $group = $group['group'];
    }

    $conph_phids = array();
    $group_phids = mpull($items, 'getPHID');
    if (($group == 'Project')
        && !empty($items)) {
      $relation_phids = array();
      foreach ($group_phids as $key => $value) {
        $conph_phid = PhabricatorEdgeQuery::loadDestinationPHIDs(
                  $value,
                LobbyGoalsHasRoomEdgeType::EDGECONST);
        $conph_phids  = array_merge($conph_phids, $conph_phid);
        $relation_phids[$value] = ['conp' => array_pop($conph_phid)];
      }
        $conph_phids = array_unique($conph_phids);
        $participant = id(new ConpherenceParticipantQuery())
                      ->withConpherencePHIDs($conph_phids)
                      ->execute();
         $project_phids = id(new ConpherenceThreadQuery())
          ->setViewer(PhabricatorUser::getOmnipotentUser());

        if (!empty($tags)) {
          $project_phids = $project_phids
          ->withTagsPHIDs($tags)
          ->withPHIDs($conph_phids)
          ->execute();
        } else {
          $project_phids = $project_phids
          ->withPHIDs($conph_phids)
          ->execute();
        }
        $project_phids = mpull($project_phids, 'getTagsPHID');
        foreach ($relation_phids as $key => $value) {
          if ($value['conp']) {
            $relation_phids[$key] = array_merge($value,
            ['proj' => $project_phids[$value['conp']]]);
          } else {
            $relation_phids[$key] = array_merge($value,
            ['proj' => null]);
          }
        }

        $projects = id(new PhabricatorProjectQuery())
                        ->setViewer(PhabricatorUser::getOmnipotentUser())
                        ->withPHIDs(array_values($project_phids))
                        ->execute();
      foreach ($projects as $key => $project) {
        $groups = array();
          foreach ($items as $goal) {
            $progress = $goal->getProgress();

            $progress_name = $progress.'%';

            $progress_tag = id(new PHUITagView())
              ->setName($progress_name)
              ->setColor(PHUITagView::COLOR_INDIGO)
              ->setType(PHUITagView::TYPE_SHADE);
            $task_tag = $this->filterTask($goal);
            if ($project->getPHID() ==
              $relation_phids[$goal->getPHID()]['proj']) {
              $item = new PHUIObjectItemView();
              $item->setHeader($goal->getTitle())
              ->setHref('/lobby/goals/'.$goal->getID().'/')
              ->addAttribute(phabricator_datetime($goal->getDateCreated(),
              $viewer))
              ->addAttribute($progress_tag);
              if ($goal->getDescription()) {
                $blocker = id(new PHUITagView())
                  ->setIcon('fa-exclamation')
                  ->setColor(PHUITagView::COLOR_RED)
                  ->setType(PHUITagView::TYPE_SHADE);
                $item->addAttribute($blocker);
              }
              $item->addAttribute($task_tag);

              if ($goal->getIsArchived() == 1) {
                $color = 'green';
                $item->setDisabled(true)
                ->setStatusIcon($icon.' '.$color);
              }
              if ($can_manage) {
                $id = $goal->getID();
                $item->addAction(
                  id(new PHUIListItemView())
                  ->setIcon('fa-edit')
                  ->setName(pht('Edit'))
                  ->setHref('/lobby/goals/edit/'.$goal->getID().'/'));
                }
                $groups[] = $item;
            }
          }
          $list_view =  id(new PHUIObjectItemListView());

          $item = $this->viewMemberConpherence($project,
          $project_phids,
          $participant);

          foreach ($groups as  $value) {
            $list_view->addItem($value);
          }

          $header = id(new PHUIHeaderView())
          ->addSigil('task-group')
          ->setHeader(pht('%s (%s)',
            $project->getName(), phutil_count($groups)))
          ->addActionItem($item);

          $lists = id(new PHUIObjectBoxView())
          ->setHeader($header)
          ->setObjectList($list_view);
          $add = id(new PHUIObjectItemView())
          ->setHeader($lists);

          $list->addItem($add);
      }

        $result = new PhabricatorApplicationSearchResultView();

        $result->setObjectList($list);
        $result->setNoDataString(pht('No goals found.'));

        return $result;
    }

    foreach ($items as $goal) {
      $progress = $goal->getProgress();

      $progress_name = $progress.'%';

      $progress_tag = id(new PHUITagView())
        ->setName($progress_name)
        ->setColor(PHUITagView::COLOR_INDIGO)
        ->setType(PHUITagView::TYPE_SHADE);
      $task_tag = $this->filterTask($goal);

      $item = new PHUIObjectItemView();
      $item->setHeader($goal->getTitle())
        ->setHref('/lobby/goals/'.$goal->getID().'/')
        ->addAttribute(phabricator_datetime($goal->getDateCreated(),
          $viewer))
        ->addAttribute($progress_tag)
        ->addAttribute($task_tag);


      if ($goal->getIsArchived() == 1) {
          $color = 'green';
          $item->setDisabled(true)
          ->setStatusIcon($icon.' '.$color);
      }
        if ($can_manage) {
        $id = $goal->getID();
        $item->addAction(
          id(new PHUIListItemView())
            ->setIcon('fa-edit')
            ->setName(pht('Edit'))
            ->setHref('/lobby/goals/edit/'.$goal->getID().'/'));
      }

      $list->addItem($item);
    }

    $result = new PhabricatorApplicationSearchResultView();
    $result->setObjectList($list);
    $result->setNoDataString(pht('No goals found.'));

    return $result;
  }
  // This method create to generete view of members conpherence
  private function viewMemberConpherence($project, $project_phids,
    $participant) {
      $participant_phids = array();
      $conp_phids = array_flip($project_phids);

      foreach ($participant as $value) {
        if ($value->getConpherencePHID() ===
          $conp_phids[$project->getPHID()]) {
          $participant_phids[] = $value->getParticipantPHID();
        }
      }

      if (empty($participant_phids)) {
        $participant_phids = ['PHID-USER-NONE'];
      }

      $users = id(new PhabricatorPeopleQuery())
                  ->setViewer(PhabricatorUser::getOmnipotentUser())
                  ->withPHIDs($participant_phids)
                  ->needProfile(true)
                  ->needProfileImage(true)
                  ->execute();

      $list_member = array();
      $count  = 0;
      // This variable contain limit view of member conpherence
      // you change if it needed
      $limit_user = 25;
      foreach ($users as $user) {
        if ($count >= $limit_user) {
          $list_member[] = id(new PHUIBadgeMiniView())
          ->setHeader(pht('And %d more members',
          (count($users) - $count)));
          break;
        } else {
          $list_member[] = id(new PHUIBadgeMiniView())
          ->setImage($user->getProfileImageURI())
          ->setHeader(pht('%s', $user->getUserName()));
          $count++;
        }
      }

      return $list_member;
  }

  protected function getNewUserBody() {
    $create_button = id(new PHUIButtonView())
      ->setTag('a')
      ->setText(pht('Add a goals'))
      ->setHref('/lobby/goals/edit/form/default')
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

  protected function newExportFields() {
    $fields = array(
      id(new PhabricatorStringExportField())
        ->setKey('title')
        ->setLabel(pht('Title')),
      id(new PhabricatorPHIDExportField())
        ->setKey('noteType')
        ->setLabel(pht('Note Type')),
      id(new PhabricatorStringExportField())
        ->setKey('content')
        ->setLabel(pht('Content')),
      id(new PhabricatorPHIDExportField())
        ->setKey('conph')
        ->setLabel(pht('Conpherence')),
      id(new PhabricatorPHIDExportField())
        ->setKey('project')
        ->setLabel(pht('Projects')),
      id(new PhabricatorStringExportField())
        ->setKey('htmlContent')
        ->setLabel(pht('Html Content')),
      id(new PhabricatorStringExportField())
        ->setKey('owner')
        ->setLabel(pht('Owner')),
      id(new PhabricatorStringExportField())
        ->setKey('ownerPHID')
        ->setLabel(pht('Owner PHID')),
      id(new PhabricatorStringListExportField())
        ->setKey('maniphest')
        ->setLabel('Maniphest PHIDs'),
      id(new PhabricatorStringListExportField())
        ->setKey('task')
        ->setLabel('Maniphest'),
      id(new PhabricatorStringExportField())
        ->setKey('progress')
        ->setLabel('Progress'),
    );

    return $fields;
  }

  protected function newExportData(array $goals) {
    $viewer = $this->requireViewer();
    $export = array();
    foreach ($goals as $goal) {
      $task_phids = PhabricatorEdgeQuery::loadDestinationPHIDs(
        $goal->getPHID(),
        LobbyGoalsHasManiphestEdgeType::EDGECONST);

      $user = $goal->loadUser();
      $engine = PhabricatorMarkupEngine::getEngine()
      ->setConfig('viewer', PhabricatorUser::getOmnipotentUser());

      $parsed_content = $engine->markupText($goal->getContent());
      if ($parsed_content instanceof PhutilSafeHTML) {
        $parsed_content = $parsed_content->getHTMLContent();
      }
      $conph_phid = PhabricatorEdgeQuery::loadDestinationPHIDs(
        $goal->getPHID(),
      LobbyGoalsHasRoomEdgeType::EDGECONST);
      $project_phids = null;

      if (!empty($conph_phid)) {
        $project_phids = id(new ConpherenceThreadQuery())
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->withPHIDs($conph_phid)
        ->execute();
        $project_phids = mpull($project_phids, 'getTagsPHID');
        $project_phids = $project_phids[end($conph_phid)];
      }
      $maniphest = array();
      if (!empty($task_phids)) {
        $tasks = id(new ManiphestTaskQuery())
              ->setViewer(PhabricatorUser::getOmnipotentUser())
              ->withPHIDs($task_phids)
              ->execute();

        foreach ($tasks as $key => $task) {
          $maniphest[] = pht('T%d (pts:%d | ptsQA:%d | progress:%d)',
          $task->getID(),
          $task->getPoints(),
          $task->getPointsQA(),
          $task->getProgress());
        }
      }
      $export[] = array(
        'title' => $goal->getTitle(),
        'noteType' => $goal->getNoteType(),
        'content' => $goal->getContent(),
        'conph' => array_pop($conph_phid),
        'project' => $project_phids,
        'progress' => $goal->getProgress(),
        'htmlContent' => $parsed_content,
        'owner' => $user->getFullName(),
        'ownerPHID' => $user->getPHID(),
        'maniphest' =>  $task_phids,
        'task' =>  $maniphest,
      );

    }
    return $export;
  }
}
