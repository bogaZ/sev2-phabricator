<?php

final class ProjectColumnUpdateConduitAPIMethod
  extends ProjectConduitAPIMethod {
    public function getAPIMethodName() {
      return 'project.column.edit';
    }

    public function getMethodDescription() {
      return pht('Edit column properties.');
    }

    protected function defineParamTypes() {
      return array(
        'columnPHID'        =>  'required string',
        'name'              =>  'required string',
      );
    }

    protected function defineReturnType() {
      return 'dict';
    }

    protected function execute(ConduitAPIRequest $request) {
      $column_phid = $request->getValue('columnPHID');
      $name = $request->getValue('name');

      $viewer = $request->getViewer();

      if (!$column_phid) {
        return $this->setMessage('columnPHID cannot be null', true);
      }

      if (!$name) {
        return $this->setMessage('name cannot be null', true);
      }

      $column = id(new PhabricatorProjectColumnQuery())
          ->setViewer($viewer)
          ->withPHIDs(array($column_phid))
          ->executeOne();

      if ($column) {
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

        return $this->setMessage('Colum successfully edited', false);
      } else {
        return $this->setMessage('Column not found', true);
      }

    }
}