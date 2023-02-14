<?php

final class ProjectCreateWorkboardConduitAPIMethod
  extends ProjectConduitAPIMethod {
    public function getAPIMethodName() {
      return 'project.workboard.create';
    }

    public function getMethodDescription() {
      return pht('Create Project Workboard for the first time.');
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

      if (!$project_phid) {
        return $this->setMessage('projectPHID cannot be null', true);
      }

      $project = id(new PhabricatorProjectQuery())
        ->setViewer($viewer)
        ->withPHIDs(array($project_phid))
        ->executeOne();

      if ($project) {

        $workboard = id(new PhabricatorProjectColumnQuery())
          ->setViewer($viewer)
          ->withProjectPHIDs(array($project_phid))
          ->execute();

        if ($workboard) {
          return $this->setMessage('This project has already workboard created', true);
        }

        $column = PhabricatorProjectColumn::initializeNewColumn($viewer)
          ->setSequence(0)
          ->setProperty('isDefault', true)
          ->setProjectPHID($project->getPHID())
          ->save();

        $xactions = array();
        $xactions[] = id(new PhabricatorProjectTransaction())
          ->setTransactionType(
              PhabricatorProjectWorkboardTransaction::TRANSACTIONTYPE)
          ->setNewValue(1);

        $aphront_request = new AphrontRequest('', '');

        id(new PhabricatorProjectTransactionEditor())
          ->setActor($viewer)
          ->setContentSourceFromRequest($aphront_request)
          ->setContinueOnNoEffect(true)
          ->setContinueOnMissingFields(true)
          ->applyTransactions($project, $xactions);

        return $this->setMessage('Workboard created successfully', false);
      } else {
        return $this->setMessage('Project with this phid is not found', true);
      }
    }
}