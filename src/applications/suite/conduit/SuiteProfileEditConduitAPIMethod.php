<?php

final class SuiteProfileEditConduitAPIMethod extends
  PhabricatorEditEngineAPIMethod {

  public function getAPIMethodName() {
    return 'suite.profile.edit';
  }

  public function getMethodStatus() {
    return self::METHOD_STATUS_FROZEN;
  }

  public function newEditEngine() {
    return new SuiteProfileEditEngine();
  }

  public function getMethodSummary() {
    return pht(
      'Apply transactions to edit a suite profile. '.
      '(Users can not be created via '.
      'the API.)');
  }
}
