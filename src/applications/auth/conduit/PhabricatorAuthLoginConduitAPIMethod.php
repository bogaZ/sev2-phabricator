<?php

final class PhabricatorAuthLoginConduitAPIMethod
  extends PhabricatorAuthConduitAPIMethod {

  public function getAPIMethodName() {
    return 'auth.login';
  }

  public function shouldRequireAuthentication() {
    return false;
  }

  public function getMethodDescription() {
    return pht('User Login.');
  }

  protected function defineParamTypes() {
    return array(
      'username' => 'optional string',
      'password' => 'required string',
    );
  }

  protected function defineReturnType() {
    return 'result-set';
  }

  protected function execute(ConduitAPIRequest $request) {
    $username_or_email = $request->getValue('username');
    $password = $request->getValue('password');

    if (!$username_or_email) {
      return $this->setMessage('Username or email cannot be null', true);
    }

    if (!$password) {
      return $this->setMessage('Password cannot be null', true);
    }

    $user = id(new PhabricatorUser())->loadOneWhere(
      'username = %s',
      $username_or_email);

    if (!$user) {
      $user = PhabricatorUser::loadOneWithEmailAddress(
        $username_or_email);
    }

    if ($user) {
      $password_envelope = new PhutilOpaqueEnvelope($password);
      $aphront_request = new AphrontRequest('', '');
      $content_source = PhabricatorContentSource::newFromRequest(
        $aphront_request);
      $engine = id(new PhabricatorAuthPasswordEngine())
        ->setViewer($user)
        ->setContentSource($content_source)
        ->setPasswordType(PhabricatorAuthPassword::PASSWORD_TYPE_ACCOUNT)
        ->setObject($user);

      if ($engine->isValidPassword($password_envelope)) {
        $token = $this->generateAPIToken($user);

        return array(
          'data' => array(
            'user' => $user->getPHID(),
            'token' => $token,
          ),
        );
      }
    }

    return $this->setMessage('Incorrect username or password', true);
  }

  private function generateAPIToken(PhabricatorUser $user) {
    $tokens = id(new PhabricatorConduitTokenQuery())
    ->setViewer($user)
    ->withObjectPHIDs(array($user->getPHID()))
    ->withExpired(false)
    ->requireCapabilities(
      array(
        PhabricatorPolicyCapability::CAN_VIEW,
        PhabricatorPolicyCapability::CAN_EDIT,
      ))
    ->execute();

    $current_token = null;
    if (count($tokens) == 0) {
      // create token if there is no token yet
      $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
        $current_token = PhabricatorConduitToken::initializeNewToken(
          $user->getPHID(),
          PhabricatorConduitToken::TYPE_STANDARD);
        $current_token->save();
      unset($unguarded);
    } else {
      // use current token
      $current_token = current($tokens);
    }

    return $current_token->getToken();
  }
}
