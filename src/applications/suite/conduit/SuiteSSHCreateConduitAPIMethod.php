<?php

final class SuiteSSHCreateConduitAPIMethod
  extends SuiteConduitAPIMethod {

  public function getAPIMethodName() {
    return 'suite.ssh.create';
  }

  public function getMethodStatus() {
    return self::METHOD_STATUS_UNSTABLE;
  }

  public function getMethodDescription() {
    return pht('Create user SSH public key.');
  }

  protected function defineParamTypes() {
    return array(
      'data_base64' => 'required non empty base64-bytes',
      'name' => 'required non empty string',
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

    // Get current key
    $v_name = $request->getValue('name');
    $v_key = $this->decodeBase64($request->getValue('data_base64'));

    if (empty($v_name) || empty($v_key)) {
      throw new ConduitException('ERR_EMPTY_DATA');
    }

    $new = false;
    $current_keys = id(new PhabricatorAuthSSHKeyQuery())
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->withObjectPHIDs(array($user->getPHID()))
        ->withIsActive(true)
        ->execute();
    $current_key = head($current_keys);
    $xactions = array();
    if (!$current_key) {
      // Create a new public key
      $current_key = PhabricatorAuthSSHKey::initializeNewSSHKey($viewer, $user);

      $validation_exception = null;
      $type_create = PhabricatorTransactions::TYPE_CREATE;

      $xactions[] = id(new PhabricatorAuthSSHKeyTransaction())
        ->setTransactionType(PhabricatorTransactions::TYPE_CREATE);

      $new = true;
    }

    $type_name = PhabricatorAuthSSHKeyTransaction::TYPE_NAME;
    $type_key = PhabricatorAuthSSHKeyTransaction::TYPE_KEY;

    $xactions[] = id(new PhabricatorAuthSSHKeyTransaction())
      ->setTransactionType($type_name)
      ->setNewValue($v_name);

    $xactions[] = id(new PhabricatorAuthSSHKeyTransaction())
      ->setTransactionType($type_key)
      ->setNewValue($v_key);

    $editor = id(new PhabricatorAuthSSHKeyEditor())
      ->setActor($viewer)
      ->setContentSource($request->newContentSource())
      ->setContinueOnNoEffect(true);

    try {
      $editor->applyTransactions($current_key, $xactions);
    } catch (PhabricatorApplicationTransactionValidationException $ex) {
      throw new ConduitException('ERR_FAILED_OPERATION');
    }

    return array(
      'name' => $current_key->getName(),
      'type' => $current_key->getKeyType(),
      'comment' => $current_key->getKeyComment(),
      'isNew' => $new,
    );
  }

  protected function decodeBase64($data) {
    $data = base64_decode($data, $strict = true);
    if ($data === false) {
      throw new Exception(pht('Unable to decode base64 data!'));
    }
    return $data;
  }
}
