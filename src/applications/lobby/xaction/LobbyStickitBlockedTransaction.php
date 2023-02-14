<?php

final class LobbyStickitBlockedTransaction
  extends LobbyStickitTransactionType {

  const TRANSACTIONTYPE = 'lobby:stickit-blocked';

  public function generateOldValue($object) {
    return $object->getDescription();
  }

  public function applyInternalEffects($object, $value) {
    $object->setDescription($value);
  }

  public function getTitle() {
    return pht(
      '%s changed blocked from %s to %s.',
      $this->renderAuthor(),
      $this->renderOldValue(),
      $this->renderNewValue());
  }
}
