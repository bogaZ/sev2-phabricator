<?php

final class PhabricatorRepositoryBuildInfoTransaction
  extends PhabricatorModularTransaction {

  public function getApplicationTransactionType() {
    return PhabricatorRepositoryBuildInfoPHIDType::TYPECONST;
  }

  public function getBaseTransactionClass() {
    return 'PhabricatorRepositoryBuildInfoTransactionType';
  }

  public function getApplicationName() {
    return 'repository';
  }

}
