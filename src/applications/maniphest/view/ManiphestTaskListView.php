<?php

final class ManiphestTaskListView extends ManiphestView {

  private $tasks;
  private $handles;
  private $showBatchControls;
  private $noDataString;

  public function setTasks(array $tasks) {
    assert_instances_of($tasks, 'ManiphestTask');
    $this->tasks = $tasks;
    return $this;
  }

  public function setHandles(array $handles) {
    assert_instances_of($handles, 'PhabricatorObjectHandle');
    $this->handles = $handles;
    return $this;
  }

  public function setShowBatchControls($show_batch_controls) {
    $this->showBatchControls = $show_batch_controls;
    return $this;
  }

  public function setNoDataString($text) {
    $this->noDataString = $text;
    return $this;
  }

  private function getCustomReviewer($task) {
    $viewer = $this->getViewer();

    $field_list = PhabricatorCustomField::getObjectFields(
      $task,
      PhabricatorCustomField::ROLE_VIEW);

    $field_list
      ->setViewer($viewer)
      ->readFieldsFromStorage($task);

    $reviewer_phid = null;

    if ($field_list) {
      foreach ($field_list->getFields() as $key => $value) {
        $field = explode(':', $key);
        if (end($field) == 'reviewer' &&
          !is_null($value->getProxy()->getFieldValue())) {
          $reviewer_phid = $value->getProxy()->getFieldValue()[0];
        }
      }
    }
    return $reviewer_phid;
  }

  public function render() {
    $handles = $this->handles;

    require_celerity_resource('maniphest-task-summary-css');

    $list = new PHUIObjectItemListView();

    if ($this->noDataString) {
      $list->setNoDataString($this->noDataString);
    } else {
      $list->setNoDataString(pht('No tasks.'));
    }

    $status_map = ManiphestTaskStatus::getTaskStatusMap();
    $color_map = ManiphestTaskPriority::getColorMap();
    $priority_map = ManiphestTaskPriority::getTaskPriorityMap();

    if ($this->showBatchControls) {
      Javelin::initBehavior('maniphest-list-editor');
    }

    if (!$this->tasks) {
      return $list->setNoDataString(pht('No tasks.'));
    }

    $author_phids = mpull($this->tasks, 'getAuthorPHID');
    $authors = id(new PhabricatorPeopleQuery())
              ->withPHIDs($author_phids)
              ->setViewer(PhabricatorUser::getOmnipotentUser())
              ->execute();

    $authors = mpull($authors, null, 'getPHID');

    foreach ($this->tasks as $task) {
      $item = id(new PHUIObjectItemView())
        ->setUser($this->getUser())
        ->setObject($task)
        ->setObjectName('T'.$task->getID())
        ->setHeader($task->getTitle())
        ->setHref('/T'.$task->getID());

      $reviewer_phid = $this->getCustomReviewer($task);

      if ($task->getOwnerPHID()) {
        $owner = $handles[$task->getOwnerPHID()];
        $item->addByline(pht('Assigned: %s', $owner->renderLink()));
      }
      if ($reviewer_phid) {
        $reviewer =  $handles[$reviewer_phid];
        $item->addByline(pht('Reviewer: %s', $reviewer->renderLink()));
      }
      if ($task->getOwnerQAPHID()) {
        $owner = $handles[$task->getOwnerQAPHID()];
        $item->addByline(pht('Assigned QA: %s', $owner->renderLink()));
      }

      $status = $task->getStatus();
      $pri = idx($priority_map, $task->getPriority());
      $status_name = idx($status_map, $task->getStatus());
      $tooltip = pht('%s, %s', $status_name, $pri);

      $icon = ManiphestTaskStatus::getStatusIcon($task->getStatus());
      $color = idx($color_map, $task->getPriority(), 'grey');
      if ($task->isClosed()) {
        $item->setDisabled(true);
        $color = 'grey';
      }

      $item->setStatusIcon($icon.' '.$color, $tooltip);

      if ($task->isClosed()) {
        $closed_epoch = $task->getClosedEpoch();

        // We don't expect a task to be closed without a closed epoch, but
        // recover if we find one. This can happen with older objects or with
        // lipsum test data.
        if (!$closed_epoch) {
          $closed_epoch = $task->getDateModified();
        }

        $item->addIcon(
          'fa-check-square-o grey',
          phabricator_datetime($closed_epoch, $this->getUser()));
      } else {
        $item->addIcon(
          'none',
          phabricator_datetime($task->getDateModified(), $this->getUser()));
      }

        $progress = $task->getProgress();

        $progress_name = $progress.'%';

        $progress_tag = id(new PHUITagView())
          ->setName($progress_name)
          ->setColor(PHUITagView::COLOR_INDIGO)
          ->setType(PHUITagView::TYPE_SHADE);

        $points = $task->getPoints();

        $points_tag = id(new PHUITagView())
          ->setName($points)
          ->setColor(PHUITagView::COLOR_BLUE)
          ->setType(PHUITagView::TYPE_SHADE);

        $points_qa = $task->getPointsQA();

        $points_qa_tag = id(new PHUITagView())
          ->setName($points_qa)
          ->setColor(PHUITagView::COLOR_GREEN)
          ->setType(PHUITagView::TYPE_SHADE);

      if ($this->showBatchControls) {
        $item->addSigil('maniphest-task');
      }

      $subtype = $task->newSubtypeObject();
      if ($subtype && $subtype->hasTagView()) {
        $subtype_tag = $subtype->newTagView()
          ->setSlimShady(true);
        $item->addAttribute($subtype_tag);
      }

      $project_handles = array_select_keys(
        $handles,
        array_reverse($task->getProjectPHIDs()));

      $item->addAttribute(
        id(new PHUIHandleTagListView())
          ->setLimit(4)
          ->setNoDataString(pht('No Projects'))
          ->setSlim(true)
          ->setHandles($project_handles));

      $item->addAttribute($progress_tag);

      if ($points !== null) {
        $item->addAttribute($points_tag);
      }

      if ($points_qa !== null) {
        $item->addAttribute($points_qa_tag);
      }
      $author = $authors[$task->getAuthorPHID()];
      if ($author->getIsForDev()) {
        $dev = id(new PHUITagView())
              ->setIcon('fa-warning')
              ->setSlimShady(true)
              ->setColor('red')
              ->setType(PHUITagView::TYPE_OBJECT)
              ->setName('Development');
        $item->addAttribute($dev);
      }

      $item->setMetadata(
        array(
          'taskID' => $task->getID(),
        ));

      if ($this->showBatchControls) {
        $href = new PhutilURI('/maniphest/task/edit/'.$task->getID().'/');
        $item->addAction(
          id(new PHUIListItemView())
            ->setIcon('fa-pencil')
            ->addSigil('maniphest-edit-task')
            ->setHref($href));
      }

      $list->addItem($item);
    }

    return $list;
  }

  public static function loadTaskHandles(
    PhabricatorUser $viewer,
    array $tasks) {
    assert_instances_of($tasks, 'ManiphestTask');

    $phids = array();
    foreach ($tasks as $task) {
      $assigned_phid = $task->getOwnerPHID();
      $assigned_qa_phid = $task->getOwnerQAPHID();
      if ($assigned_phid) {
        $phids[] = $assigned_phid;
      }
      $field_list = PhabricatorCustomField::getObjectFields(
        $task,
        PhabricatorCustomField::ROLE_VIEW);

      $field_list
        ->setViewer($viewer)
        ->readFieldsFromStorage($task);

      $reviewer_phid = null;

      if ($field_list) {
        foreach ($field_list->getFields() as $key => $value) {
          $field = explode(':', $key);
          if (end($field) == 'reviewer' &&
            !is_null($value->getProxy()->getFieldValue())) {
            $reviewer_phid = $value->getProxy()->getFieldValue()[0];
          }
        }
      }
      if ($reviewer_phid) {
        $phids[] = $reviewer_phid;
      }
      if ($assigned_qa_phid) {
        $phids[] = $assigned_qa_phid;
      }
      foreach ($task->getProjectPHIDs() as $project_phid) {
        $phids[] = $project_phid;
      }
    }

    if (!$phids) {
      return array();
    }

    return id(new PhabricatorHandleQuery())
      ->setViewer($viewer)
      ->withPHIDs($phids)
      ->execute();
  }

}
