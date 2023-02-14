<?php

final class LobbyStickitArchiveTransaction
  extends LobbyStickitTransactionType {

  const TRANSACTIONTYPE = 'lobby:stickit-archive';
  public function generateOldValue($object) {
    return $object->getIsArchived();
  }

  public function applyInternalEffects($object, $value) {
    $object->setIsArchived((int)$value);
  }

  public function getTitle() {
    $new = $this->getNewValue();
    if ($new) {
      return pht(
        '%s complete this stickit.',
        $this->renderAuthor());
    } else {
      return pht(
        '%s incomplete this stickit.',
        $this->renderAuthor());
    }
  }

  public function getTitleForFeed() {
    $new = $this->getNewValue();
    if ($new) {
      return pht(
        '%s complete stickit %s.',
        $this->renderAuthor(),
        $this->renderObject());
    } else {
      return pht(
        '%s incomplete stickit %s.',
        $this->renderAuthor(),
        $this->renderObject());
    }
  }

  public function getIcon() {
    $new = $this->getNewValue();
    if ($new) {
      return 'fa-ban';
    } else {
      return 'fa-check';
    }
  }

  public function getColor() {
    $new = $this->getNewValue();
    if ($new) {
      return 'indigo';
    }
  }
}
