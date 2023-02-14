<?php

final class LobbyStateCurrentTaskTransaction
  extends LobbyStateTransactionType {

  const TRANSACTIONTYPE = 'lobby:state-currenttask';


  public function generateOldValue($object) {
    return $object->getCurrentTask();
  }

  public function generateNewValue($object, $value) {
    return $value;
  }

  public function applyInternalEffects($object, $value) {
    $object->setCurrentTask($value);
  }

  public function getTitle() {
      return pht(
        '%s working on %s.',
        $this->renderAuthor(),
        $this->renderNewValue());
  }

}
