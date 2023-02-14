<?php

final class ProjectColumnHideConduitAPIMethod
  extends ProjectConduitAPIMethod {
    public function getAPIMethodName() {
      return 'project.column.visibility';
    }

    public function getMethodDescription() {
      return pht('Hide or Show column visibility.');
    }

    protected function defineParamTypes() {
      return array(
        'columnPHID'       => 'required string',
        'status'           => 'required string | active, hide'
      );
    }

    protected function defineReturnType() {
      return 'dict';
    }

    protected function execute(ConduitAPIRequest $request) {
      $column_phid = $request->getValue('columnPHID');
      $status = $request->getValue('status');
      $viewer = $request->getViewer();

      $status_validation = ['active', 'hide'];

      if (!in_array($status, $status_validation)) {
        return $this->setMessage('status is not valid value', true);
      }

      if (!$column_phid) {
        return $this->setMessage('columnPHID cannot be null', true);
      }

      $column = id(new PhabricatorProjectColumnQuery())
        ->setViewer($viewer)
        ->withPHIDs(array($column_phid))
        ->executeOne();

      if ($column) {
        if ($status == 'active') {
          $message = 'Column has been activated';
          $new_status = PhabricatorProjectColumn::STATUS_ACTIVE;
        }

        if ($status == 'hide') {
          $message = 'Column has been hidden';
          $new_status = PhabricatorProjectColumn::STATUS_HIDDEN;
        }

        $type_status =
          PhabricatorProjectColumnStatusTransaction::TRANSACTIONTYPE;

        $xactions = array(
          id(new PhabricatorProjectColumnTransaction())
            ->setTransactionType($type_status)
            ->setNewValue($new_status),
        );

        $aphront_request = new AphrontRequest('', '');

        $editor = id(new PhabricatorProjectColumnTransactionEditor())
          ->setActor($viewer)
          ->setContinueOnNoEffect(true)
          ->setContinueOnMissingFields(true)
          ->setContentSourceFromRequest($aphront_request)
          ->applyTransactions($column, $xactions);

        return $this->setMessage($message, false);
      } else {
        return $this->setMessage('Column not found', true);
      }

    }
}