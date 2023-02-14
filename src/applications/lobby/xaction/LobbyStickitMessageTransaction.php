<?php

final class LobbyStickitMessageTransaction
  extends LobbyStickitTransactionType {

  const TRANSACTIONTYPE = 'lobby:stickit-message';

  public function generateOldValue($object) {
    return $object->getMessage();
  }

  public function applyInternalEffects($object, $value) {
    $object->setMessage($value);
  }

  public function getTitle() {
    return pht(
      '%s changed action items from %s to %s.',
      $this->renderAuthor(),
      $this->renderOldValue(),
      $this->renderNewValue());
  }
}
