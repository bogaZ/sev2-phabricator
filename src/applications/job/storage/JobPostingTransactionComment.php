<?php

final class JobPostingTransactionComment
  extends PhabricatorApplicationTransactionComment {

  public function getApplicationTransactionObject() {
    return new JobPostingTransaction();
  }

}
