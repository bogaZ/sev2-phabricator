<?php

abstract class SuiteBalanceTransactionType
  extends PhabricatorModularTransactionType {

    public function getIcon() {
      $old = $this->getOldValue();
      $new = $this->getNewValue();

      if ($new > 0) {
        return 'fa fa-plus-square';
      } else if ($new < 0) {
        return 'fa fa-minus-square';
      }

      return 'fa fa-square';
    }

    public function getColor() {
      $old = $this->getOldValue();
      $new = $this->getNewValue();

      if ($new > 0) {
        return 'green';
      } else if ($new < 0) {
        return 'red';
      }

      return 'yellow';
    }

  }
