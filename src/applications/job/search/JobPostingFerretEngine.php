<?php

final class JobPostingFerretEngine
  extends PhabricatorFerretEngine {

  public function getApplicationName() {
    return 'job';
  }

  public function getScopeName() {
    return 'posting';
  }

  public function newSearchEngine() {
    return new JobPostingSearchEngine();
  }

}
