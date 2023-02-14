<?php

final class PhabricatorChangesetCachePurger
  extends PhabricatorCachePurger {

  const PURGERKEY = 'changeset';

  public function purgeCache() {
    $table = new DifferentialChangeset();
    $conn = $table->establishConnection('w');

    queryfx(
      $conn,
      'TRUNCATE TABLE %T',
      sev2table(DifferentialChangeset::TABLE_CACHE));
  }

}
