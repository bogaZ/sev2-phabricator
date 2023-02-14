<?php

final class PhabricatorSearchSchemaSpec
  extends PhabricatorConfigSchemaSpec {

  public function buildSchemata() {
    $this->buildEdgeSchemata(new PhabricatorProfileMenuItemConfiguration());

    $this->buildRawSchema(
      'search',
      sev2table(PhabricatorSearchDocument::STOPWORDS_TABLE),
      array(
        'value' => 'sort32',
      ),
      array());
  }

}
