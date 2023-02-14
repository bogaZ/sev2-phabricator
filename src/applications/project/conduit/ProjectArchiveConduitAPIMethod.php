<?php

final class ProjectArchiveConduitAPIMethod
  extends ProjectConduitAPIMethod {
    public function getAPIMethodName() {
      return 'project.status';
    }

    public function getMethodDescription() {
      return pht('Archive or Active project.');
    }

    protected function defineParamTypes() {
      return array(
        'projectPHID'      => 'required string',
        'status'           => 'required string | archive, active'
      );
    }

    protected function defineReturnType() {
      return 'dict';
    }

    protected function execute(ConduitAPIRequest $request) {
      $project_phid = $request->getValue('projectPHID');
      $status = $request->getValue('status');
      $viewer = $request->getViewer();

      if (!$project_phid) {
        return array(
          'message' => 'projectPHID cannot be null',
          'error' => true
        );
      }

      if (!$status) {
        return array(
          'message' => 'status cannot be null',
          'error' => true
        );
      }

      $project = id(new PhabricatorProjectQuery())
        ->setViewer($viewer)
        ->withPHIDs(array($project_phid))
        ->executeOne();

      if ($project) {
        if ($project->isArchived() && $status == 'archive') {
          return array(
            'message' => 'Cannot archive this project, because this project already archived ',
            'error' => true,
          );
        }

        $new_status = PhabricatorProjectStatus::STATUS_ACTIVE;
        $message = 'Project successfully activated';

        if ($status == 'archive') {
          $new_status = PhabricatorProjectStatus::STATUS_ARCHIVED;
          $message = 'Project successfully archived';
        }

        $xactions = array();

        $xactions[] = id(new PhabricatorProjectTransaction())
          ->setTransactionType(
              PhabricatorProjectStatusTransaction::TRANSACTIONTYPE)
          ->setNewValue($new_status);

        $aphront_request = new AphrontRequest('', '');
        id(new PhabricatorProjectTransactionEditor())
          ->setActor($viewer)
          ->setContentSourceFromRequest($aphront_request)
          ->setContinueOnNoEffect(true)
          ->setContinueOnMissingFields(true)
          ->applyTransactions($project, $xactions);

        return array(
          'message' => $message,
          'error' => false,
        );

      } else {
        return array(
          'message' => 'Project not found',
          'error' => true
      );
    }
  }
}