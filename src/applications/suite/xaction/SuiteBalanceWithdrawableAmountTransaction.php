<?php

final class SuiteBalanceWithdrawableAmountTransaction
  extends SuiteBalanceTransactionType {

  const TRANSACTIONTYPE = 'suite:balance-withdrawable';

  public function generateOldValue($object) {
    return $this->getValueForAmount($object->getWithdrawableAmount());
  }

  public function generateNewValue($object, $value) {
    return $this->getValueForAmount($value);
  }

  public function applyInternalEffects($object, $value) {
    $sum = $this->getOldValue() + $value;
    $object->setWithdrawableAmount($sum);
  }

  public function getTitle() {
    $old = $this->getOldValue();
    $new = $this->getNewValue();

    if ($new < 0) {
      // Debit operation
      return pht(
          'balance withdrawable amount debited for %d',
          -($this->getNewValue()));
    } else {
      return pht(
          'balance withdrawable amount credited for %d',
          $this->getNewValue());
    }
  }

  public function shouldHideForFeed() {
    // Don't publish feed stories about balance changes, since this can be
    // a sensitive action.
    return true;
  }

  private function getValueForAmount($value) {
    if (!strlen($value)) {
      $value = null;
    }
    if ($value !== null) {
      $value = (double)$value;
    }
    return $value;
  }

  public function getTransactionTypeForConduit($xaction) {
    return 'withdrawable_amount';
  }

  public function getFieldValuesForConduit($xaction, $data) {
    return array(
      'old' => $xaction->getOldValue(),
      'new' => $xaction->getNewValue(),
    );
  }
}
