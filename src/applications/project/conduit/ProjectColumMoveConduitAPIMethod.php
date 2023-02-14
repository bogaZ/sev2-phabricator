<?php

final class ProjectColumMoveConduitAPIMethod
  extends ProjectConduitAPIMethod {
    public function getAPIMethodName() {
      return 'project.column.maniphest.move';
    }

    public function getMethodDescription() {
      return pht('Move Maniphest to other column.');
    }

    protected function defineParamTypes() {
      return array(
        'projectPHID'      => 'required string',
        'columnPHID'       => 'required string',
        'taskPHID'         => 'required string | array',
      );
    }

    protected function defineReturnType() {
      return 'dict';
    }

    protected function execute(ConduitAPIRequest $request) {
      $project_phid = $request->getValue('projectPHID');
      $column_phid = $request->getValue('columnPHID');
      $task_phids = $request->getValue('taskPHID');

      $viewer = $request->getViewer();

      $is_success = array();

      if (!$project_phid) {
        return $this->setMessage('projectPHID cannot be null', true);
      }

      if (!$column_phid) {
        return $this->setMessage('columnPHID cannot be null', true);
      }

      if (!$task_phids) {
        return $this->setMessage('task_phid cannot be null', true);
      }

      if (is_string($task_phids)) {
        $task_phids = array($task_phids);
      }

      foreach ($task_phids as $phid) {
        $task = id(new ManiphestTaskQuery())
          ->setViewer($viewer)
          ->withPHIDs(array($phid))
          ->needProjectPHIDs(true)
          ->requireCapabilities(
            array(
              PhabricatorPolicyCapability::CAN_VIEW,
              PhabricatorPolicyCapability::CAN_EDIT,
            ))
          ->executeOne();

        $column = id(new PhabricatorProjectColumnQuery())
          ->setViewer($viewer)
          ->withPHIDs(array($column_phid))
          ->executeOne();

        if ($column) {
          $xactions = array();
          $xactions[] = id(new ManiphestTransaction())
            ->setTransactionType(PhabricatorTransactions::TYPE_COLUMNS)
            ->setNewValue(
              array(
                array(
                  'columnPHID' => $column->getPHID(),
                ),
              ));

          $engine = id(new PhabricatorBoardLayoutEngine())
            ->setViewer($viewer)
            ->setBoardPHIDs(array($project_phid))
            ->setObjectPHIDs(array($phid))
            ->executeLayout();

          $aphront_request = new AphrontRequest('', '');
          $editor = id(new ManiphestTransactionEditor())
            ->setActor($viewer)
            ->setContinueOnMissingFields(true)
            ->setContinueOnNoEffect(true)
            ->setContentSourceFromRequest($aphront_request)
            ->applyTransactions($task, $xactions);
          $is_success[] = array($phid => 'success');
        } else {
          $is_success[] = array($phid => 'error');
        }
      }
      return $this->setMessage($is_success, false);
    }
}
