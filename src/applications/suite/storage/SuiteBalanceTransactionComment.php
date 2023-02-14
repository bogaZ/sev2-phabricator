<?php

final class SuiteBalanceTransactionComment
  extends PhabricatorApplicationTransactionComment {

  public function getApplicationTransactionObject() {
    return new SuiteBalanceTransaction();
  }

}
