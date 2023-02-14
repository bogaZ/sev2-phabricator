<?php

final class PhabricatorProjectSchemaSpec extends PhabricatorConfigSchemaSpec {

  public function buildSchemata() {
    $this->buildEdgeSchemata(new PhabricatorProject());

    $this->buildRawSchema(
      id(new PhabricatorProject())->getApplicationName(),
      sev2table(PhabricatorProject::TABLE_DATASOURCE_TOKEN),
      array(
        'id' => 'auto',
        'projectID' => 'id',
        'token' => 'text128',
      ),
      array(
        'PRIMARY' => array(
          'columns' => array('id'),
          'unique' => true,
        ),
        'token' => array(
          'columns' => array('token', 'projectID'),
          'unique' => true,
        ),
        'projectID' => array(
          'columns' => array('projectID'),
        ),
      ));


  }

}
