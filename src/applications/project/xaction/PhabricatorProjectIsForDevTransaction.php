<?php

final class PhabricatorProjectIsForDevTransaction
  extends PhabricatorProjectTransactionType {

  const TRANSACTIONTYPE = 'project:is_for_dev';

  public function generateOldValue($object) {
    return $object->getIsForDev();
  }

  public function generateNewValue($object, $value) {
    return (int)$value;
  }

  public function applyInternalEffects($object, $value) {
    $object->setIsForDev($value);
  }

  public function getTitle() {
    if ($this->getNewValue()) {
      return pht(
        '%s mark this project for devepeloment purpose.',
        $this->renderAuthor());
    } else {
      return pht(
        '%s mark this project closed for development purpose.',
        $this->renderAuthor());
    }
  }

  public function getTitleForFeed() {
    if ($this->getNewValue()) {
      return pht(
        '%s marked %s for development.',
        $this->renderAuthor(),
        $this->renderObject());
    } else {
      return pht(
        '%s marked %s closed for development.',
        $this->renderAuthor(),
        $this->renderObject());
    }
  }

  public function getIcon() {
    $new = $this->getNewValue();
    return PhabricatorProjectIconSet::getIconIcon($new);
  }

}
