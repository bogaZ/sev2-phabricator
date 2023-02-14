<?php

final class ProjecColumnOrderConduitAPIMethod
  extends ProjectConduitAPIMethod {
    public function getAPIMethodName() {
      return 'project.column.order';
    }

    public function getMethodDescription() {
      return pht('Reorder Column.');
    }

    protected function defineParamTypes() {
      return array(
        'projectPHID'      => 'required string',
        'columnPHID'       => 'required string',
        'sequence'         => 'required string'
      );
    }

    protected function defineReturnType() {
      return 'dict';
    }

    protected function execute(ConduitAPIRequest $request) {
      $project_phid = $request->getValue('projectPHID');
      $column_phid = $request->getValue('columnPHID');
      $new_sequence = $request->getValue('sequence');

      $viewer = $request->getViewer();

      if (!$project_phid) {
        return $this->setMessage('projectPHID cannot be null', true);
      }

      if (!$column_phid) {
        return $this->setMessage('columnPHID cannot be null', true);
      }

      $project = id(new PhabricatorProjectQuery())
        ->setViewer($viewer)
        ->withPHIDs(array($project_phid))
        ->executeOne();

      if (!$project) {
        return $this->setMessage('Project not found', true);
      }

      $columns = id(new PhabricatorProjectColumnQuery())
        ->setViewer($viewer)
        ->withProjectPHIDs(array($project->getPHID()))
        ->execute();
      $columns = msort($columns, 'getSequence');

      $new_map = array();
      foreach ($columns as $phid => $column) {
        $value = $column->getSequence();
        if ($column->getPHID() == $column_phid) {
          $value = $new_sequence;
        } else if ($column->getSequence() >= $new_sequence) {
          $value = $value + 1;
        }
        $new_map[$phid] = $value;
      }

      asort($new_map);

      $project->openTransaction();
      $sequence = 0;
      foreach ($new_map as $phid => $ignored) {
        $new_value = $sequence++;
        $cur_value = $columns[$phid]->getSequence();
        if ($new_value != $cur_value) {
          $columns[$phid]->setSequence($new_value)->save();
        }
      }
      $project->saveTransaction();

      return $this->setMessage("Column successfully reordered", false);
    }
}