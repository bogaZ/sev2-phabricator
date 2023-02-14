<?php

final class PhabricatorCoursepathMailReceiver
  extends PhabricatorObjectMailReceiver {

  public function isEnabled() {
    return PhabricatorApplication::isClassInstalled(
      'PhabricatorCoursepathApplication');
  }

  protected function getObjectPattern() {
    return 'CRSI[1-9]\d*';
  }

  protected function loadObject($pattern, PhabricatorUser $viewer) {
    $id = (int)substr($pattern, 4);

    return id(new CoursepathItemQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->executeOne();
  }

  protected function getTransactionReplyHandler() {
    return new PhabricatorCoursepathReplyHandler();
  }

}
