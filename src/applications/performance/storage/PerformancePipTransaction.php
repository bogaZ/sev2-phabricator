<?php

final class PerformancePipTransaction
  extends PhabricatorModularTransaction {

  public function getTableName() {
    return sev2table('piptransaction');
  }

  public function getApplicationName() {
    return 'performance';
  }

  public function getApplicationTransactionType() {
    return PerformancePipPHIDType::TYPECONST;
  }

  public function getBaseTransactionClass() {
    return 'PerformancePipTransactionType';
  }
}
