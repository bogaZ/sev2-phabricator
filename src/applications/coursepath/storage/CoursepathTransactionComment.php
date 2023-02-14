<?php

final class CoursepathTransactionComment
  extends PhabricatorApplicationTransactionComment {

  public function getApplicationTransactionObject() {
    return new CoursepathTransaction();
  }

}
