<?php

final class SuiteProfileMailReceiver
  extends PhabricatorObjectMailReceiver {

  public function isEnabled() {
    return PhabricatorApplication::isClassInstalled(
      'PhabricatorSuiteApplication');
  }

  protected function getObjectPattern() {
    return 'SUTP[1-9]\d*';
  }

  protected function loadObject($pattern, PhabricatorUser $viewer) {
    $id = (int)substr($pattern, 1);

    return id(new SuiteProfile())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->executeOne();
  }

  protected function getTransactionReplyHandler() {
    return new SuiteProfileReplyHandler();
  }

}
