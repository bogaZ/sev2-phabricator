<?php

final class ProjectCreateMilestoneAPIMethod
  extends ProjectConduitAPIMethod {
    public function getAPIMethodName() {
      return 'project.milestone.create';
    }

    public function getMethodDescription() {
      return pht('Create new Milestone.');
    }

    protected function defineParamTypes() {
      return array(
        'projectPHID'      => 'required string',
        'name'             => 'required string',
        'description'      => 'optional string',
        'hashtags'         => 'optional string',
        'start'            => 'optional epoch',
        'end'              => 'optional epoch',
        'isForDev'         => 'optional bool',
      );
    }

    protected function defineReturnType() {
      return 'dict';
    }

    protected function execute(ConduitAPIRequest $request) {
      $project_phid = $request->getValue('projectPHID');
      $name = $request->getValue('name');
      $description = $request->getValue('description');
      $hashtags = $request->getValue('hashtags');
      $start = (int) $request->getValue('start');
      $end = (int) $request->getValue('end');
      $is_for_dev = (bool) $request->getValue('isForDev');

      $viewer = $request->getViewer();

      if (!$project_phid) {
        return $this->setMessage('projectPHID cannot be null', true);
      }

      if (!$name) {
        return $this->setMessage('name cannot be null', true);
      }

      $project = id(new PhabricatorProjectQuery())
          ->setViewer($viewer)
          ->needMembers(true)
          ->withPHIDs(array($project_phid))
          ->executeOne();

      if (!$project) {
        return $this->setMessage('Project not found', true);
      }

      $new_project = PhabricatorProject::initializeNewProject($viewer);

      $xactions = array();

      $xactions[] = id(new PhabricatorProjectTransaction())
        ->setTransactionType(
          PhabricatorProjectNameTransaction::TRANSACTIONTYPE)
        ->setNewValue($name);

      $xactions[] = id(new PhabricatorProjectTransaction())
        ->setTransactionType(
          PhabricatorProjectNewMilestoneTransaction::TRANSACTIONTYPE)
        ->setNewValue($project_phid);

      if ($start) {
        $datetime_start = AphrontFormDateControlValue::newFromEpoch(
          $viewer, $start);
        $xactions[] = id(new PhabricatorProjectTransaction())
        ->setTransactionType(
          PhabricatorProjectStartDateTransaction::TRANSACTIONTYPE)
        ->setNewValue($datetime_start);
      }

      if ($end) {
        $datetime_end = AphrontFormDateControlValue::newFromEpoch(
          $viewer, $end);
        $xactions[] = id(new PhabricatorProjectTransaction())
        ->setTransactionType(
          PhabricatorProjectEndDateTransaction::TRANSACTIONTYPE)
        ->setNewValue($datetime_end);
      }

      if ($is_for_dev != null) {
        $xactions[] = id(new PhabricatorProjectTransaction())
        ->setTransactionType(
          PhabricatorProjectIsForDevTransaction::TRANSACTIONTYPE)
        ->setNewValue($is_for_dev);
      }

      $editor = id(new PhabricatorProjectTransactionEditor())
        ->setActor($viewer)
        ->setContinueOnNoEffect(true)
        ->setContentSource($request->newContentSource());

      $editor->applyTransactions($new_project, $xactions);

      $conn_w = id(new PhabricatorProjectCustomFieldStorage())
        ->establishConnection('w');

      queryfx(
        $conn_w,
        'INSERT INTO %T (objectPHID, fieldIndex, fieldValue)
        VALUES (%s, %s, %s)',
        sev2table('project_customfieldstorage'),
        $new_project->getPHID(),
        '0.9QWd3nmyTs',
        $description
      );

      return array(
        'message' => 'Milestone successfully created',
        'error' => false
      );
    }
  }
