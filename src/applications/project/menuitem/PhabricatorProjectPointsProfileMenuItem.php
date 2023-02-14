<?php

final class PhabricatorProjectPointsProfileMenuItem
  extends PhabricatorProfileMenuItem {

  const MENUITEMKEY = 'project.points';

  public function getMenuItemTypeName() {
    return pht('Project Points');
  }

  private function getDefaultName() {
    return pht('Points Bar');
  }

  // $limit move into this function so only need change this code
  private function limit() {
    $limit = 250;
    return $limit;
  }

  public function shouldEnableForObject($object) {
    $viewer = $this->getViewer();

    // Only render this element for milestones.
    // This code commented so all project can show SP
    // DON'T DELETE THIS CODE !!!!
    if ($object->getIcon() !== 'project') {
      if (!$object->isMilestone()) {
        return false;
      }
    }

    // Don't show if points aren't configured.
    if (!ManiphestTaskPoints::getIsEnabled()) {
      return false;
    }

    // Points are only available if Maniphest is installed.
    $class = 'PhabricatorManiphestApplication';
    if (!PhabricatorApplication::isClassInstalledForViewer($class, $viewer)) {
      return false;
    }

    return true;
  }

  public function getDisplayName(
    PhabricatorProfileMenuItemConfiguration $config) {
    return $this->getDefaultName();
  }

  public function buildEditEngineFields(
    PhabricatorProfileMenuItemConfiguration $config) {
    return array(
      id(new PhabricatorInstructionsEditField())
        ->setValue(
          pht(
            'This is a progress bar which shows how many points of work '.
            'are complete within the milestone. It has no configurable '.
            'settings.')),
    );
  }
// This function to get list QA SP
  private function newMenuItemViewQAList(
    PhabricatorProfileMenuItemConfiguration $config) {
      $viewer = $this->getViewer();
      $project = $config->getProfileObject();

      // By default workspace need some limit, cause some project
      // cannot be estimate the limit of task
      // so this code will be commented
      // but don't remove this code
      // $limit = $this->limit();

      $tasks = id(new ManiphestTaskQuery())
        ->setViewer($viewer)
        ->withEdgeLogicPHIDs(
          PhabricatorProjectObjectHasProjectEdgeType::EDGECONST,
          PhabricatorQueryConstraint::OPERATOR_AND,
          array($project->getPHID()))
        // ->setLimit($limit + 1)
        ->execute();

      $error = array();
      // if (count($tasks) > $limit) {
      //   $error[] =
      //     pht(
      //       'Too many tasks (%s).',
      //       new PhutilNumber($limit));
      // }
      // change from only milestone -> project
      if (!$tasks) {
        $error[] = pht('This project has no tasks.');
      }

      $statuses = array();
      $points_done = 0;
      $points_total = 0;
      $no_points = 0;
      foreach ($tasks as $task) {
        $points = (double)$task->getPointsQA();

        if ($points === null) {
          $no_points++;
          continue;
        }

        if (!$points) {
          continue;
        }

        $status = $task->getStatus();
        if (empty($statuses[$status])) {
          $statuses[$status] = 0;
        }
        $statuses[$status] += $points;

        if (ManiphestTaskStatus::isClosedStatus($status)) {
          $points_done += $points;
        }

        $points_total += $points;
      }

      if ($no_points == count($tasks)) {
        $error[] = pht('No tasks have points assigned.');
      }

      if (!$points_total) {
        $error[] = pht('No tasks have positive points.');
      }

      $label = pht(
        '%s of %s %s',
        new PhutilNumber($points_done, 2),
        new PhutilNumber($points_total, 2),
        'Story Points QA');

      $bar = id(new PHUISegmentBarView())
        ->setLabel($label);

      $map = ManiphestTaskStatus::getTaskStatusMap();
      $statuses = array_select_keys($statuses, array_keys($map));

      foreach ($statuses as $status => $points) {
        if (!$points) {
          continue;
        }

        if (!ManiphestTaskStatus::isClosedStatus($status)) {
          continue;
        }

        $color = ManiphestTaskStatus::getStatusColor($status);
        if (!$color) {
          $color = 'sky';
        }

        $tooltip = pht(
          '%s %s',
          new PhutilNumber($points),
          ManiphestTaskStatus::getTaskStatusName($status));

        $bar->newSegment()
          ->setWidth($points / $points_total)
          ->setColor($color)
          ->setTooltip($tooltip);
      }

      if ($error) {
        $bar->setLabel(head($error));
      }

      $bar = phutil_tag(
        'div',
        array(
          'class' => 'phui-profile-segment-bar',
        ),
        $bar);

      $item = $this->newItemView();

      $item->newProgressBar($bar);

      return array(
        $item,
      );

    }

  // This code use for showing story points
  protected function newMenuItemViewList(
    PhabricatorProfileMenuItemConfiguration $config) {
    $viewer = $this->getViewer();
    $project = $config->getProfileObject();

    $limit = $this->limit();

    $tasks = id(new ManiphestTaskQuery())
      ->setViewer($viewer)
      ->withEdgeLogicPHIDs(
        PhabricatorProjectObjectHasProjectEdgeType::EDGECONST,
        PhabricatorQueryConstraint::OPERATOR_AND,
        array($project->getPHID()))
      // ->setLimit($limit + 1)
      ->execute();

    $error = array();
    // if (count($tasks) > $limit) {
    //   $error[] =
    //     pht(
    //       'Too many tasks (%s).',
    //       new PhutilNumber($limit));
    // }

    if (!$tasks) {
      $error[] = pht('This project has no tasks.');
    }

    $statuses = array();
    $points_done = 0;
    $points_total = 0;
    $no_points = 0;
    foreach ($tasks as $task) {
      $points = $task->getPoints();

      if ($points === null) {
        $no_points++;
        continue;
      }

      if (!$points) {
        continue;
      }

      $status = $task->getStatus();
      if (empty($statuses[$status])) {
        $statuses[$status] = 0;
      }
      $statuses[$status] += $points;

      if (ManiphestTaskStatus::isClosedStatus($status)) {
        $points_done += $points;
      }

      $points_total += $points;
    }

    if ($no_points == count($tasks)) {
      $error[] = pht('No tasks have points assigned.');
    }

    if (!$points_total) {
      $error[] = pht('No tasks have positive points.');
    }

    // This code use for showing story points
    $label = pht(
      '%s of %s %s',
      new PhutilNumber($points_done, 2),
      new PhutilNumber($points_total, 2),
      ManiphestTaskPoints::getPointsLabel());

    $bar = id(new PHUISegmentBarView())
      ->setLabel($label);

    $map = ManiphestTaskStatus::getTaskStatusMap();
    $statuses = array_select_keys($statuses, array_keys($map));

    foreach ($statuses as $status => $points) {
      if (!$points) {
        continue;
      }

      if (!ManiphestTaskStatus::isClosedStatus($status)) {
        continue;
      }

      $color = ManiphestTaskStatus::getStatusColor($status);
      if (!$color) {
        $color = 'sky';
      }

      $tooltip = pht(
        '%s %s',
        new PhutilNumber($points),
        ManiphestTaskStatus::getTaskStatusName($status));

      $bar->newSegment()
        ->setWidth($points / $points_total)
        ->setColor($color)
        ->setTooltip($tooltip);
    }

    if ($error) {
      $bar->setLabel(head($error));
    }

    $bar = phutil_tag(
      'div',
      array(
        'class' => 'phui-profile-segment-bar',
      ),
      $bar);

    $item = $this->newItemView();

    $item->newProgressBar($bar);
    $item_qa = $this->newMenuItemViewQAList($config);
    $item = array_merge([$item], $item_qa);
    return $item;
  }

}
