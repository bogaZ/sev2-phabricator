<?php

final class CoursepathItemTestTransactionQuery
  extends PhabricatorApplicationTransactionQuery {

  public function getTemplateApplicationTransaction() {
    return new CoursepathItemTestTransaction();
  }

}
