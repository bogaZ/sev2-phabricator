<?php

final class JobPostingTransactionQuery
  extends PhabricatorApplicationTransactionQuery {

  public function getTemplateApplicationTransaction() {
    return new JobPostingTransaction();
  }

}
