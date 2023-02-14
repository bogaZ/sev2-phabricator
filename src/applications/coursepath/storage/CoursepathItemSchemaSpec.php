<?php

final class CoursepathItemSchemaSpec
  extends PhabricatorConfigSchemaSpec {

  public function buildSchemata() {
    $this->buildEdgeSchemata(new CoursepathItem());
  }

}
