<?php

final class ProjectColumnCreateAPIMethod
  extends ProjectConduitAPIMethod {
    public function getAPIMethodName() {
      return 'project.column.create';
    }

    public function getMethodDescription() {
      return pht('Create new column.');
    }

    protected function defineParamTypes() {
      return array(
        'projectPHID'      => 'required string',
        'name'             => 'required string'
      );
    }

    protected function defineReturnType() {
      return 'dict';
    }

    protected function execute(ConduitAPIRequest $request) {
      $project_phid = $request->getValue('projectPHID');
      $name = $request->getValue('name');
      $viewer = $request->getViewer();

      if (!$project_phid) {
        return $this->setMessage('projectPHID cannot be null', true);
      }

      if (!$name) {
        return $this->setMessage('name cannot be null', true);
      }

      $project = id(new PhabricatorProjectQuery())
          ->setViewer($viewer)
          ->withPHIDs(array($project_phid))
          ->executeOne();

      if ($project) {

        $column = PhabricatorProjectColumn::initializeNewColumn($viewer);

        $column->setProjectPHID($project->getPHID());
        $column->attachProject($project);

        $columns = id(new PhabricatorProjectColumnQuery())
          ->setViewer($viewer)
          ->withProjectPHIDs(array($project->getPHID()))
          ->execute();

        $new_sequence = 1;
        if ($columns) {
          $values = mpull($columns, 'getSequence');
          $new_sequence = max($values) + 1;
        }
        $column->setSequence($new_sequence);

        $xactions = array();

        $type_name = PhabricatorProjectColumnNameTransaction::TRANSACTIONTYPE;

        if (!$column->getProxy()) {
          $xactions[] = id(new PhabricatorProjectColumnTransaction())
            ->setTransactionType($type_name)
            ->setNewValue($name);
        }

        $aphront_request = new AphrontRequest('', '');

        $editor = id(new PhabricatorProjectColumnTransactionEditor())
          ->setActor($viewer)
          ->setContinueOnNoEffect(true)
          ->setContentSourceFromRequest($aphront_request)
          ->applyTransactions($column, $xactions);

        return $this->setMessage('Column successfully created', false);
      } else {
        return $this->setMessage('Project not found', true);
      }
    }
}