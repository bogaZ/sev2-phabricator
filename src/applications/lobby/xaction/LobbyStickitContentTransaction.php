<?php

final class LobbyStickitContentTransaction
  extends LobbyStickitTransactionType {

  const TRANSACTIONTYPE = 'lobby:stickit-content';

  public function generateOldValue($object) {
    return $object->getContent();
  }

  public function applyInternalEffects($object, $value) {
    $object->setContent($value);
  }

  public function getTitle() {
    return pht(
      '%s changed content from %s to %s.',
      $this->renderAuthor(),
      $this->renderOldValue(),
      $this->renderNewValue());
  }
}
