<?php

final class UserCheckInConduitAPIMethod extends UserConduitAPIMethod {

  public function getAPIMethodName() {
    return 'user.checkin';
  }

  public function getMethodDescription() {
    return pht('Save information when user last check in');
  }

  protected function defineParamTypes() {
    return array();
  }

  protected function defineReturnType() {
    return 'nonempty dict<string, wild>';
  }

  public function getRequiredScope() {
    return self::SCOPE_ALWAYS;
  }

  protected function execute(ConduitAPIRequest $request) {
    $user = $request->getUser()->getPHID();

    $ph_checkin = new PhabricatorUserCheckIn();
    $table = $ph_checkin->getTableName();
    $conn_w = $ph_checkin->establishConnection('w');

    $date_now = PhabricatorTime::getNow();

    $send_data = queryfx(
      $conn_w,
      'INSERT INTO %T
        (phid, dateCreated, dateModified, viewPolicy, editPolicy)
        VALUES (%s, %d, %d, %s, %s)',
      sev2table($table),
      $user,
      $date_now,
      $date_now,
      'users',
      'no_one');

    $data = array(
      'phid' => $user,
      'dateCreated' => $date_now,
    );

    return $data;
  }

}
