<?php

abstract class SuiteEditor
  extends PhabricatorApplicationTransactionEditor {

  public function getEditorApplicationClass() {
    return 'PhabricatorSuiteApplication';
  }

   protected function getMailSubjectPrefix() {
    return '[Suite]';
  }

}
