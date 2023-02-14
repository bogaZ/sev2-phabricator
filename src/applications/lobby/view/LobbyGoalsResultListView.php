<?php

final class LobbyGoalsResultListView extends AphrontView {

  private $baseUri;
  private $noDataString;
  private $items;
  private $user;

  public function setBaseUri($base_uri) {
    $this->baseUri = $base_uri;
    return $this;
  }

  public function setUser(PhabricatorUser $user) {
    $this->user = $user;
    return $this;
  }

  protected function getUser() {
    return $this->user;
  }

  public function setItems(array $items) {
    $this->items = $items;
    return $this;
  }

  public function getItems() {
    return $this->items;
  }

  public function render() {
    $viewer = $this->getUser();
    $items = $this->getItems();
    assert_instances_of($items, 'LobbyStickit');
    $handles = id(new PhabricatorHandleQuery())
          ->setViewer(PhabricatorUser::getOmnipotentUser())
          ->withPHIDs(mpull($items, 'getOwnerPHID'))
          ->execute();

    $lists = array();
    $type_map = ['goals' => 'goals'];
    foreach ($type_map as $type => $label) {
      $group = array();
      foreach ($items as $s) {
        if ($type == $s->getNoteType()) {
          $progress = $s->getProgress();

          $progress_name = $progress.'%';

          $progress_tag = id(new PHUITagView())
            ->setName($progress_name)
            ->setColor(PHUITagView::COLOR_INDIGO)
            ->setType(PHUITagView::TYPE_SHADE);
          $task_tag = $this->filterTask($s);

          $icon = ManiphestTaskStatus::getStatusIcon('resolved');
          $item = id(new PHUIObjectItemView())
            ->setUser($viewer)
            ->setObject($s)
            ->setHeader($s->getTitle())
            ->setHref('/lobby/goals/'.$s->getID().'/');

          if ($s->getIsArchived() == 1) {
            $color = 'green';
            $item->setDisabled(true)
            ->setStatusIcon($icon.' '.$color);
          }

          if ($s->getOwnerPHID()) {
            $owner = $handles[$s->getOwnerPHID()];
            $item->addByline(pht('By: %s', $owner->renderLink()));
          }

          $item->addIcon(
            'none',
            phabricator_datetime($s->getDateModified(), $viewer));

          $item->addAttribute(id(new PHUITagView())
                  ->setType(PHUITagView::TYPE_SHADE)
                  ->setColor(PHUITagView::COLOR_BLUE)
                  ->setIcon('fa-eye')
                  ->setSlimShady(true)
                  ->setName(pht(' %d people(s)', count($s->getSeenPHIDs()))));
          $item->addAttribute($progress_tag);
          if ($s->getDescription()) {
            $blocker = id(new PHUITagView())
              ->setIcon('fa-exclamation')
              ->setColor(PHUITagView::COLOR_RED)
              ->setType(PHUITagView::TYPE_SHADE);
            $item->addAttribute($blocker);
          }
          $item->addAttribute($task_tag);

          $group[] = $item;
        }
      }

      if (!empty($group)) {
        $list = new PHUIObjectItemListView();
        foreach ($group as $member) {
          $list->addItem($member);
        }

        $header = id(new PHUIHeaderView())
          ->addSigil('task-group')
          ->setHeader(pht('%s (%s)', $label, phutil_count($group)));

        $lists[] = id(new PHUIObjectBoxView())
          ->setHeader($header)
          ->setObjectList($list);
      }
    }
    return $lists;
  }

  private function filterTask(LobbyStickit $item) {
    $limit = 5;
    $count = 0;
    $views = id(new PHUIObjectItemView())
      ->setUser($this->getUser());

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

}
