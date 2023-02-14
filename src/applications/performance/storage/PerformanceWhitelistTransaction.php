<?php

final class PerformanceWhitelistTransaction
  extends PhabricatorModularTransaction {

  public function getTableName() {
    return sev2table('whitelisttransaction');
  }

  public function getApplicationName() {
    return 'performance';
  }

  public function getApplicationTransactionType() {
    return PerformanceWhitelistPHIDType::TYPECONST;
  }

  public function getBaseTransactionClass() {
    return 'PerformanceWhitelistTransactionType';
  }
}
