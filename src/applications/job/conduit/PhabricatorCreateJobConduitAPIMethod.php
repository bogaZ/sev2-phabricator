<?php

final class PhabricatorCreateJobConduitAPIMethod
  extends PhabricatorEditEngineAPIMethod {

  public function getAPIMethodName() {
    return 'job.posting.create';
  }

  public function newEditEngine() {
    return new PhabricatorJobPostingEditEngine();
  }

  public function getMethodSummary() {
    return pht(
      'Apply transactions to create a new job or edit an existing one.');
  }

}
