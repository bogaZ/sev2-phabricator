<?php

final class LobbyStateStatusTransaction
  extends LobbyStateTransactionType {

  const TRANSACTIONTYPE = 'lobby:state-status';

  public function generateOldValue($object) {
    return (int)$object->getStatus();
  }

  public function generateNewValue($object, $value) {
    return (int)$value;
  }

  public function applyInternalEffects($object, $value) {
    if ($this->isNewObject()) {
      $object->setStatus(1);
    } else {
      $object->setStatus((int)$value);
    }
  }


  public function getTitle() {
      $maps = LobbyState::getStatusMap();
      $const = (int) $this->renderNewValue();
      $current_status = $maps[$const];
      return pht(
        '%s is %s.',
        $this->renderAuthor(),
        $current_status);
  }

}
