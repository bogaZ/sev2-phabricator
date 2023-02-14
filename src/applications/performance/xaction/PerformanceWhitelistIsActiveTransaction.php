<?php

final class PerformanceWhitelistIsActiveTransaction
  extends PerformanceWhitelistTransactionType {

  const TRANSACTIONTYPE = 'performance:whitelist-isactive';

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
        '%s add a whitelist.',
        $this->renderAuthor());
    } else {
      return pht(
        '%s remove a whitelist.',
        $this->renderAuthor());
    }

  }

}
