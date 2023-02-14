<?php

final class LobbyModeratorChannelTransaction
  extends LobbyModeratorTransactionType {

  const TRANSACTIONTYPE = 'lobby:moderator-channel';


  public function generateOldValue($object) {
    return $object->getChannelPHID();
  }

  public function generateNewValue($object, $value) {
    return $value;
  }

  public function applyInternalEffects($object, $value) {
    $object->setChannelPHID($value);
  }

  public function getTitle() {
      return pht(
        '%s updated %s channel to %s.',
        $this->renderAuthor(),
        $this->renderOldValue(),
        $this->renderNewValue());
  }

}
