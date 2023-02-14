<?php

final class HeraldSchemaSpec extends PhabricatorConfigSchemaSpec {

  public function buildSchemata() {
    $this->buildRawSchema(
      id(new HeraldRule())->getApplicationName(),
      sev2table(HeraldRule::TABLE_RULE_APPLIED),
      array(
        'ruleID' => 'id',
        'phid' => 'phid',
      ),
      array(
        'PRIMARY' => array(
          'columns' => array('ruleID', 'phid'),
          'unique' => true,
        ),
        'phid' => array(
          'columns' => array('phid'),
        ),
      ));

    $this->buildRawSchema(
      id(new HeraldRule())->getApplicationName(),
      HeraldTranscript::getSavedHeaderTableName(),
      array(
        'phid' => 'phid',
        'header' => 'text',
      ),
      array(
        'PRIMARY' => array(
          'columns' => array('phid'),
          'unique' => true,
        ),
      ));
    $this->buildEdgeSchemata(new HeraldRule());
  }

}
