<?php

final class ProjectColumnManiphestSearchConduitAPIMethod
  extends ProjectConduitAPIMethod {
    public function getAPIMethodName() {
      return 'project.column.maniphest';
    }

    public function getMethodDescription() {
      return pht('Get Maniphest by each column.');
    }

    protected function defineParamTypes() {
      return array(
        'projectPHID'       => 'required string',
      );
    }

    protected function defineReturnType() {
      return 'dict';
    }

    protected function execute(ConduitAPIRequest $request) {
      $project_phid = $request->getValue('projectPHID');
      $viewer = $request->getViewer();
      $tasks = array();
      $results = array();

      if (!$project_phid) {
        return $this->setMessage('projectPHID cannot be null', true);
      }

      $project = id(new PhabricatorProjectQuery())
          ->setViewer($viewer)
          ->needImages(true)
          ->withPHIDs(array($project_phid))
          ->needColumns(true)
          ->executeOne();

      if (!$project) {
       return $this->setMessage('Project not found', true);
      }

      if (empty($project->getColumns())) {
        return $this->setMessage("This project doesn't have workboard", true);
      }

      // todo: remove visibilty from response
      // todo: check if something goes to this function
      if ($project->isMilestone()) {
        $maniphests = $this->getBacklogMilestoneColumns($project, $viewer);
        if ($maniphests) {
          foreach ($maniphests as $maniphest) {
            $tasks[] = $this->setManiphest($maniphest, $viewer);
          }
        }

        $columns = $this->getProjectColumn($project, $viewer);
        foreach ($columns as $column) {
          $result = array();
          if ($column->getProxy()) {
            continue;
          }
          $result['phid'] = $column->getPHID();
          $result['projectPHID'] = $project->getPHID();
          $result['name'] = $column->getDisplayName();
          $result['type'] = $column->getDisplayType();

          $result['isMilestone'] = true;
          $result['isSubproject'] = false;
          $result['isActive'] = true;

          $visibility = 'active';
          if ($column->getStatus() == PhabricatorProjectColumn::STATUS_HIDDEN) {
            $visibility = 'hidden';
          }

          $result['visibility'] = $visibility;

          $result['sequence'] = $column->getSequence();
          $result['properties'] =
            $column->getProperties() == [] ? null : $column->getProperties();
          $result['dateCreated'] = $column->getDateCreated();
          $result['dateModified'] = $column->getDateModified();

          $result['tasks'] = array();

          if ($column->getDisplayType() == "(Default)") {
            $result['tasks'] = $tasks;
          } else {
            $positions = $column->getPositions();
            foreach ($positions as $position) {
              $maniphests = $position->getManiphests();
              foreach ($maniphests as $maniphest) {
                $task = $this->setManiphest($maniphest, $viewer);
                $result['tasks'][] = $task;
              }
            }
          }
          $results[] = $result;
        }
      } else {
        $columns = $this->getProjectColumn($project, $viewer);
        foreach ($columns as $column) {
          $result = array();
          $result['phid'] = $column->getPHID();
          $result['projectPHID'] = $project->getPHID();
          $result['name'] = $column->getDisplayName();
          $result['type'] = $column->getDisplayType();

          $result['isMilestone'] = false;
          $result['isSubproject'] = false;
          $result['isActive'] = true;
          if ($column->getProxy()) {
            $is_milestone = $column->getProxy()->isMilestone();
            $parent_phid = $column->getProxy()->getParentProjectPHID();
            if (!is_null($parent_phid) && !$is_milestone) {
              $result['isSubproject'] = true;
            }

            if ($is_milestone) {
              $properties = $column->getProxy()->getProperties();
              if ($properties && $properties['endDateTime']) {
                $milestone_end_time = id(new PhutilCalendarAbsoluteDateTime())
                  ->newFromDictionary($properties['endDateTime'])
                  ->newPHPDateTime();
                if ($milestone_end_time->getTimeStamp() < time()) {
                  $result['isActive'] = false;
                }
              }
            }
            $result['isMilestone'] = $is_milestone;
            $result['projectPHID'] = $column->getProxyPHID();
          }

          $visibility = 'active';
          if ($column->getStatus() == PhabricatorProjectColumn::STATUS_HIDDEN) {
            $visibility = 'hidden';
          }

          $result['visibility'] = $visibility;

          $result['sequence'] = $column->getSequence();
          $result['properties'] =
            $column->getProperties() == [] ? null : $column->getProperties();
          $result['dateCreated'] = $column->getDateCreated();
          $result['dateModified'] = $column->getDateModified();
          $result['tasks'] = array();

          $positions = $column->getPositions();
          foreach ($positions as $position) {
            $maniphests = $position->getManiphests();
            foreach ($maniphests as $maniphest) {
              $task = $this->setManiphest($maniphest, $viewer);
              $result['tasks'][] = $task;
            }
          }
          $results[] = $result;
        }
      }

      return array(
        'data' => $results
      );
    }

    private function setManiphest($maniphest, $viewer) {
      $status_value = $maniphest->getStatus();
      $status_info = array(
        'value' => $status_value,
        'name' =>
          ManiphestTaskStatus::getTaskStatusName($status_value),
        'color' =>
          ManiphestTaskStatus::getStatusColor($status_value),
      );

      $priority_value = (int)$maniphest->getPriority();
      $priority_info = array(
        'value' => $priority_value,
        'name' =>
          ManiphestTaskPriority::getTaskPriorityName($priority_value),
        'color' =>
          ManiphestTaskPriority::getTaskPriorityColor($priority_value),
      );

      $engine = PhabricatorMarkupEngine::getEngine()
      ->setConfig('viewer', PhabricatorUser::getOmnipotentUser());

      $parsed_description = $engine->markupText($maniphest->getDescription());
      if ($parsed_description instanceof PhutilSafeHTML) {
        $parsed_description = $parsed_description->getHTMLContent();
      }

      $assigned_profile_image = null;
      $assigned = null;
      $assigned_phid = null;
      $assigned_qa_profile_image = null;
      $assigned_qa = null;
      $assigned_qa_phid = null;
      if ($maniphest->getOwnerPHID()) {
        $user_query = id(new PhabricatorPeopleQuery())
            ->setViewer($viewer)
            ->needProfileImage(true)
            ->withPHIDs(array($maniphest->getOwnerPHID()))
            ->executeOne();

        $assigned = $user_query->getUsername();
        $assigned_phid = $user_query->getPHID();
        $assigned_profile_image= $user_query->getProfileImageURI();
      }

      if ($maniphest->getOwnerQAPHID()) {

        $user_query = id(new PhabricatorPeopleQuery())
            ->setViewer($viewer)
            ->needProfileImage(true)
            ->withPHIDs(array($maniphest->getOwnerQAPHID()))
            ->executeOne();

        $assigned_qa = $user_query->getUsername();
        $assigned_qa_phid = $user_query->getPHID();
        $assigned_qa_profile_image = $user_query->getProfileImageURI();
      }

      $closed_epoch = $maniphest->getClosedEpoch();
      if ($closed_epoch !== null) {
        $closed_epoch = (int)$closed_epoch;
      }

      $task = array();
      $task['id'] = $maniphest->getID();
      $task['phid'] = $maniphest->getPHID();
      $task['title'] = $maniphest->getTitle();
      $task['description'] = $maniphest->getDescription();
      $task['ownerPHID'] = $maniphest->getOwnerPHID();
      $task['authorPHID'] = $maniphest->getAuthorPHID();
      $task['assigned'] = $assigned;
      $task['assignedPHID'] = $assigned_phid;
      $task['assignedProfileImageURI'] = $assigned_profile_image;
      $task['assignedQA'] = $assigned_qa;
      $task['assignedQAPHID'] = $assigned_qa_phid;
      $task['assignedQAProfileImageURI'] = $assigned_qa_profile_image;
      $task['htmlDescription'] = $parsed_description;
      $task['status'] = $status_info;
      $task['priority'] = $priority_info;
      $task['points'] = $maniphest->getPoints();
      $task['pointsQA'] = $maniphest->getPointsQA();
      $task['progress'] = (int)$maniphest->getProgress();
      $task['subtype'] = $maniphest->getSubtype();
      $task['dateCreated'] = $maniphest->getDateCreated();
      $task['dateModified'] = $maniphest->getDateModified();
      $task['closerPHID'] = $maniphest->getCloserPHID();
      $task['dateClosed'] = $closed_epoch;

      return $task;
    }

    private function getProjectColumn($project, $viewer) {
      $table  = new PhabricatorProjectColumn();
      $conn_r = $table->establishConnection('r');

      $columns = queryfx_all(
        $conn_r,
        'SELECT phid FROM %T WHERE projectPHID = %s OR proxyPHID = %s ',
        $table->getTableName(),
        $project->getPHID(),
        $project->getPHID());

      $column_phids = array();
      foreach ($columns as $column) {
        $column_phids[] = $column['phid'];
      }

      return id(new PhabricatorProjectColumnQuery())
            ->setViewer($viewer)
            ->needPositions(true)
            ->withPHIDs($column_phids)
            ->execute();
    }

    private function getBacklogMilestoneColumns($project, $viewer) {
      $table  = new PhabricatorProjectColumn();
      $conn_r = $table->establishConnection('r');
      $maniphests = array();

      $columns = queryfx_all(
        $conn_r,
        'SELECT * FROM %T WHERE projectPHID = %s OR proxyPHID = %s limit 2',
        $table->getTableName(),
        $project->getPHID(),
        $project->getPHID());

      $table = new PhabricatorProjectColumnPosition();
      $conn_r = $table->establishConnection('r');

      if (count($columns) > 1) {
        $check_position = queryfx_all(
          $conn_r,
          'SELECT objectPHID from %T WHERE boardPHID = %s
          and columnPHID = %s',
          $table->getTableName(),
          $project->getPHID(),
          $columns[0]['phid']
        );

        if (!$check_position) {
          return $maniphests;
        }

        $positions = queryfx_all(
          $conn_r,
          'SELECT objectPHID from %T WHERE boardPHID = %s
          or boardPHID = %s
          and columnPHID = %s
          or columnPHID = %s',
          $table->getTableName(),
          $project->getPHID(),
          $columns[1]['projectPHID'],
          $columns[1]['phid'],
          $columns[0]['phid']
        );

        $maniphest_phids = array();
        foreach ($positions as $position) {
          $maniphest_phids[] = $position['objectPHID'];
        }

        if ($maniphest_phids) {
          $maniphests = id(new ManiphestTaskQuery())
                ->setViewer($viewer)
                ->withPHIDs($maniphest_phids)
                ->execute();
        }
      }
      return $maniphests;
    }
}
