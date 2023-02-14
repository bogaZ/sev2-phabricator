<?php

final class PhabricatorProjectIsForRspTransaction
  extends PhabricatorProjectTransactionType {

  const TRANSACTIONTYPE = 'project:isforrsp';

  public function generateOldValue($object) {
    return (int)$object->getIsForRsp();
  }

  public function generateNewValue($object, $value) {
    return (int)$value;
  }

  public function applyInternalEffects($object, $value) {
    $object->setIsForRsp($value);
  }

  public function getTitle() {
    if ($this->getNewValue()) {
      return pht(
        '%s mark this project open for RSP.',
        $this->renderAuthor());
    } else {
      return pht(
        '%s mark this project closed for RSP.',
        $this->renderAuthor());
    }
  }

  public function getTitleForFeed() {
    if ($this->getNewValue()) {
      return pht(
        '%s marked %s open for RSP.',
        $this->renderAuthor(),
        $this->renderObject());
    } else {
      return pht(
        '%s marked %s closed for RSP.',
        $this->renderAuthor(),
        $this->renderObject());
    }
  }

  public function getColor() {
    $old = $this->getOldValue();

    if ($old == 0) {
      return 'green';
    } else {
      return 'yellow';
    }
  }

  public function getIcon() {
    $old = $this->getOldValue();

    if ($old == 0) {
      return 'fa-wifi';
    } else {
      return 'fa-ban';
    }
  }

}
