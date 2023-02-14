<?php

final class SuiteContentSource
  extends PhabricatorContentSource {

  const SOURCECONST = 'suite';

  public function getSourceName() {
    return pht('Suite');
  }

  public function getSourceDescription() {
    return pht('Updates from suite activities.');
  }

}
