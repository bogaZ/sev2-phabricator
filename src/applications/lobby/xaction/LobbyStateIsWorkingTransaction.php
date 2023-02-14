<?php

final class LobbyStateIsWorkingTransaction
  extends LobbyStateTransactionType {

  const TRANSACTIONTYPE = 'lobby:state-isworking';


  public function generateOldValue($object) {
    return (int)$object->getIsWorking();
  }

  public function generateNewValue($object, $value) {
    return (int)$value;
  }

  public function applyInternalEffects($object, $value) {
    $object->setIsWorking($value);
  }

  public function getTitle() {
    if ($this->getNewValue()) {
      return pht(
        '%s start working.',
        $this->renderAuthor());
    } else {
      return pht(
        '%s stopped working.',
        $this->renderAuthor());
    }
  }

}
