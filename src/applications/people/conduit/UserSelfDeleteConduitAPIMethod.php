<?php

/**
 * "Destroy user account" is not really destroying the account. Instead,
 * it is disabling and update current user email with random email address.
 * So they can register with previous email, but will be known as new account.
 */
final class UserSelfDeleteConduitAPIMethod extends UserConduitAPIMethod {

  public function getAPIMethodName() {
    return 'user.destroy';
  }

  public function getMethodDescription() {
    return pht(
      '**ALERT** By running this Conduit API, your account will be disabled'.
      ' and your email address will be updated to random email address.'.
      ' So you can register with previous email address but will be known '.
      ' as new account.'.
      ' If you are developer and accidentally delete your own account,'.
      ' contact the administrator immediately.');
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

  public function generateRandomString($length) {
    $chars = '0123456789abcdefghijklmnopqrstuvwxyz';
    $char_length = strlen($chars);
    $random = '';
    for ($i = 0; $i < $length; $i++) {
        $random .= $chars[rand(0, $char_length - 1)];
    }
    return $random;
}

  protected function execute(ConduitAPIRequest $request) {
    $user_phid = $request->getUser()->getPHID();
    $mail = pht('%s@example.com', $this->generateRandomString(30));

    $ph_user = new PhabricatorUser();
    $ph_user_table = $ph_user->getTableName();
    $ph_user_conn = $ph_user->establishConnection('w');

    $ph_mail = new PhabricatorUserEmail();
    $ph_mail_table = $ph_mail->getTableName();
    $ph_mail_conn = $ph_mail->establishConnection('w');

    $date_now = PhabricatorTime::getNow();

    queryfx(
      $ph_user_conn,
      'UPDATE %T SET isDisabled = %d, dateModified=%d '.
      'WHERE phid=%s',
      sev2table($ph_user_table),
      1,
      $date_now,
      $user_phid);

    queryfx(
      $ph_mail_conn,
      'UPDATE %T SET address=%s, dateModified=%d '.
      'WHERE userPHID = %s ',
      sev2table($ph_mail_table),
      $mail,
      $date_now,
      $user_phid);

    $data = array(
      'phid' => $user_phid,
      'status' => pht('Your account has been destroyed.'),
    );

    return $data;
  }

}
