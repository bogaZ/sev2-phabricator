<?php

final class CoursepathItemTestSubmissionTransactionQuery
  extends PhabricatorApplicationTransactionQuery {

  public function getTemplateApplicationTransaction() {
    return new CoursepathItemTestSubmissionTransaction();
  }

}
