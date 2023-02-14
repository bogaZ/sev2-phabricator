<?php

final class CoursepathItemTransactionQuery
  extends PhabricatorApplicationTransactionQuery {

  public function getTemplateApplicationTransaction() {
    return new CoursepathTransaction();
  }

}
