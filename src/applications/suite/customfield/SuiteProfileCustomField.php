<?php

abstract class SuiteProfileCustomField
  extends PhabricatorCustomField {

    protected function isEditable() {
      return true;
    }
}
