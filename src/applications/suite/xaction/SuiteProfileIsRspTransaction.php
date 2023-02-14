<?php

final class SuiteProfileIsRspTransaction
  extends SuiteProfileTransactionType {

  const TRANSACTIONTYPE = 'suite:profile-rsp';

  public function generateOldValue($object) {
    return (bool)$object->getIsRsp();
  }

  public function generateNewValue($object, $value) {
    return (bool)$value;
  }

  public function applyInternalEffects($object, $value) {
    if ($this->isNewObject()) {
      $object->setIsRsp(0);
    } else {
      $object->setIsRsp((int)$value);
    }
  }


  public function getTitle() {
    if ($this->getNewValue()) {
      return pht(
        '%s disabled RSP flag.',
        $this->renderAuthor());
    } else {
      return pht(
        '%s enabled RSP flag.',
        $this->renderAuthor());
    }

  }

}
