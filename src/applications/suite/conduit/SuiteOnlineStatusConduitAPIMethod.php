<?php

final class SuiteOnlineStatusConduitAPIMethod extends SuiteConduitAPIMethod {

  public function getAPIMethodName() {
    return 'suite.online.status';
  }

  public function getMethodStatus() {
    return self::METHOD_STATUS_FROZEN;
  }

  public function getMethodDescription() {
    return pht('Toggle suite online status.');
  }

  protected function defineParamTypes() {
    return array(
      'userPHID' => 'required user phid',
      'status' => 'current online status',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $this->getViewer();

    $current_status = filter_var($request->getValue('status'), FILTER_VALIDATE_BOOLEAN);
    if ($current_status === null || !is_bool($current_status)) {
      throw new ConduitException('ERR_INVALID_STATUS');
    }

    $user_phid = $request->getValue('userPHID');

    $user = id(new PhabricatorPeopleQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withPHIDs(array($user_phid))
      ->executeOne();

    if (!$user) {
      throw new ConduitException('ERR_USER_NOT_FOUND');
    }

    $this->enforceSuiteOnly($user);

    if ($current_status != $user->getIsSuiteOnline()) {
      $xactions = array();

      $xactions[] = id(new PhabricatorUserTransaction())
        ->setTransactionType(
          PhabricatorUserSuiteOnlineTransaction::TRANSACTIONTYPE)
        ->setNewValue($current_status);

      id(new PhabricatorUserTransactionEditor())
        ->setActor($user)
        ->setActingAsPHID($user->getPHID())
        ->setContentSource($request->newContentSource())
        ->setContinueOnMissingFields(true)
        ->setContinueOnNoEffect(true)
        ->applyTransactions($user, $xactions);
    }

    return array(
      'online_status' =>  $user->getIsSuiteOnline(),
    );
  }

}
