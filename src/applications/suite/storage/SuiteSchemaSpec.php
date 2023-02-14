<?php

final class SuiteSchemaSpec
  extends PhabricatorConfigSchemaSpec {

  public function buildSchemata() {
    $this->buildEdgeSchemata(new SuiteBalance());
  }

}
