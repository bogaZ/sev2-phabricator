<?php

final class LobbyStickitTitleTransaction
  extends LobbyStickitTransactionType {

  const TRANSACTIONTYPE = 'lobby:stickit-title';

  public function generateOldValue($object) {
    return $object->getTitle();
  }

  public function applyInternalEffects($object, $value) {
    $object->setTitle($value);
  }

  public function getTitle() {
    return pht(
      '%s changed title from %s to %s.',
      $this->renderAuthor(),
      $this->renderOldValue(),
      $this->renderNewValue());
  }
}
