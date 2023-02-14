<?php

final class SuiteSSHInfoConduitAPIMethod
  extends SuiteConduitAPIMethod {

  public function getAPIMethodName() {
    return 'suite.ssh.info';
  }

  public function getMethodStatus() {
    return self::METHOD_STATUS_UNSTABLE;
  }

  public function getMethodDescription() {
    return pht('Get user SSH public key.');
  }

  protected function defineParamTypes() {
    return array();
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

    // Get current key
    $keys = array();
    $current_keys = id(new PhabricatorAuthSSHKeyQuery())
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->withObjectPHIDs(array($user->getPHID()))
        ->withIsActive(true)
        ->execute();

    foreach($current_keys as $current_key) {
      $pub = pht('%s %s %s',
            $current_key->getKeyType(),
            $current_key->getKeyBody(),
            $current_key->getKeyComment());
      $keys[] = array(
        'name' => $current_key->getName(),
        'public_key_base64' => base64_encode($pub),
      );
    }

    return $keys;
  }
}
