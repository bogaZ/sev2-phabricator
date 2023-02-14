<?php

final class PhabricatorAuthRegisterConduitAPIMethod
  extends PhabricatorAuthConduitAPIMethod {

  public function getAPIMethodName() {
    return 'auth.register';
  }

  public function shouldRequireAuthentication() {
    return false;
  }

  public function getMethodDescription() {
    return pht('User Register.');
  }

  protected function defineParamTypes() {
    return array(
      'username' => 'required string',
      'realname' => 'required string',
      'email' => 'reuqired string',
      'password' => 'required string',
      'confirmPassword' => 'required string',
    );
  }

  protected function defineReturnType() {
    return 'result-set';
  }

  protected function execute(ConduitAPIRequest $request) {
    $username = $request->getValue('username');
    $realname = $request->getValue('realname');
    $email = $request->getValue('email');
    $password = $request->getValue('password');
    $confirm_password = $request->getValue('confirmPassword');

    if ($password !== $confirm_password) {
      return $this->setMessage('The password and confirmation do not match',
        true);
    }

    $username_validation = id(new PhabricatorPeopleQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withUsernames(array($username))
      ->executeOne();

    if ($username_validation) {
      return $this->setMessage('Another user already has that username',
        false);
    }

    $email_validation = id(new PhabricatorPeopleQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withEmails(array($email))
      ->executeOne();

    if ($email_validation) {
      return $this->setMessage('Another user already has that email', false);
    }


    // set email
    $email_obj = id(new PhabricatorUserEmail())
      ->setAddress($email)
      ->setIsVerified(1);

    // initiate user object
    $new_user = new PhabricatorUser();
    $new_user->setUsername($username);
    $new_user->setRealname($realname);

    $approved = PhabricatorUser::isNotRefactorian($email)
    && !PhabricatorUser::isWorkspaceAdmin($email) ? 0 : 1;
    $new_user->setIsApproved((int)!$approved);

    if (PhabricatorUser::isWorkspaceAdmin($email)) {
      $new_user->setIsAdmin(1);
    }

    $new_user->openTransaction();

    $editor = id(new PhabricatorUserEditor())
      ->setActor($new_user);

    // create new user
    $editor->createNewUser($new_user, $email_obj, false);

    // set password
    $password_envelope = new PhutilOpaqueEnvelope($password);
    $account_type = PhabricatorAuthPassword::PASSWORD_TYPE_ACCOUNT;

    $password_object = PhabricatorAuthPassword::initializeNewPassword(
      $new_user,
      $account_type);

    $password_object
      ->setPassword($password_envelope, $new_user)
      ->save();

    // save user
    $new_user->saveTransaction();

    return $this->setMessage('User successfully created', false);
  }


}
