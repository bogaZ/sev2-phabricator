<?php

final class LobbyStateDeviceTransaction
  extends LobbyStateTransactionType {

  const TRANSACTIONTYPE = 'lobby:state-device';

  public function generateOldValue($object) {
    return $object->getDevice();
  }

  public function applyInternalEffects($object, $value) {
    $object->setDevice($value);
  }

  public function getTitle() {
    return pht(
      '%s changed device from %s to %s.',
      $this->renderAuthor(),
      $this->renderOldValue(),
      $this->renderNewValue());
  }

  public function validateTransactions($object, array $xactions) {
    $errors = array();

    if ($this->isEmptyTextTransaction($object->getDevice(), $xactions)) {
      $errors[] = $this->newRequiredError(
        pht('You must have a device.'));
    }

    $max_length = (int)$object->getColumnMaximumByteLength('device');
    foreach ($xactions as $xaction) {
      $new_value = $xaction->getNewValue();
      $new_length = strlen($new_value);
      if ($new_length > $max_length) {
        $errors[] = $this->newInvalidError(
          pht('The device value can be no longer than %s characters.',
          new PhutilNumber($max_length)));
      }
    }

    return $errors;
  }

}
