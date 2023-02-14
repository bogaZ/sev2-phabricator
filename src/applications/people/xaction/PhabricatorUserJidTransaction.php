<?php

final class PhabricatorUserJidTransaction
  extends PhabricatorUserTransactionType {

  const TRANSACTIONTYPE = 'user.jid';

  public function generateOldValue($object) {
    return $object->getJid();
  }

  public function generateNewValue($object, $value) {
    return $value;
  }

  public function applyInternalEffects($object, $value) {
    $object->setJid($value);
  }

  public function getTitle() {
    return pht(
      '%s renamed this user from %s to %s.',
      $this->renderAuthor(),
      $this->renderOldValue(),
      $this->renderNewValue());
  }


  public function validateTransactions($object, array $xactions) {
    $actor = $this->getActor();
    $errors = array();

    return $errors;
  }

}
