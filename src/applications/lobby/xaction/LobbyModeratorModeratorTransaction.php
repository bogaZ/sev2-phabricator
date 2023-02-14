<?php

final class LobbyModeratorModeratorTransaction
  extends LobbyModeratorTransactionType {

  const TRANSACTIONTYPE = 'lobby:moderator-moderator';


  public function generateOldValue($object) {
    return $object->getModeratorPHID();
  }

  public function generateNewValue($object, $value) {
    return $value;
  }

  public function applyInternalEffects($object, $value) {
    $object->setModeratorPHID($value);
  }

  public function getTitle() {
      return pht(
        '%s updated %s to %s.',
        $this->renderAuthor(),
        $this->renderOldValue(),
        $this->renderNewValue());
  }

}
