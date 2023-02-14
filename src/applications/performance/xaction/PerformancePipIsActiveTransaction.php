<?php

final class PerformancePipIsActiveTransaction
  extends PerformancePipTransactionType {

  const TRANSACTIONTYPE = 'performance:pip-isactive';

  public function generateOldValue($object) {
    return (bool)$object->getIsActive();
  }

  public function generateNewValue($object, $value) {
    return (bool)$value;
  }

  public function applyInternalEffects($object, $value) {
    if ($this->isNewObject()) {
      $object->setIsActive(1);
    } else {
      $object->setIsActive((int)$value);
    }
  }


  public function getTitle() {
    if ($this->getNewValue()) {
      return pht(
        '%s enabled PIP.',
        $this->renderAuthor());
    } else {
      return pht(
        '%s disabled PIP.',
        $this->renderAuthor());
    }

  }

}
