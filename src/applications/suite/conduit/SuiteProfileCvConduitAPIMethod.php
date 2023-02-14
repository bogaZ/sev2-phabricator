<?php

final class SuiteProfileCvConduitAPIMethod
  extends SuiteConduitAPIMethod {

  public function getAPIMethodName() {
    return 'suite.profile.cv';
  }

  public function getMethodStatus() {
    return self::METHOD_STATUS_FROZEN;
  }

  public function getMethodDescription() {
    return pht('Set user CV.');
  }

  protected function defineParamTypes() {
    return array(
      'data_json' => 'required non empty json',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $this->getViewer();
    $user = $request->getUser();

    if (!$user) {
      throw new ConduitException('ERR_USER_NOT_FOUND');
    }

    $this->enforceSuiteOnly($user);

    $profile = SuiteProfileQuery::loadProfileForUser($user,
      $request->newContentSource());

    // Get Doc type
    $cv = $request->getValue('data_json');
    if ($cv === null || empty($cv)) {
      throw new ConduitException('ERR_EMPTY_DATA');
    }


    $profile->setCv($cv);
    $profile->save();

    return array(
      'cv' => $cv,
    );
  }
}
