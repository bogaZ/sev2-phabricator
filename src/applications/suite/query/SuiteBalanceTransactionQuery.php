<?php

final class SuiteBalanceTransactionQuery
  extends PhabricatorApplicationTransactionQuery {

  public function getTemplateApplicationTransaction() {
    return new SuiteBalanceTransaction();
  }

}
