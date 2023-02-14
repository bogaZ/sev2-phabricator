<?php

final class PhabricatorJobPostingSearchConduitAPIMethod
  extends PhabricatorSearchEngineAPIMethod {

  public function getAPIMethodName() {
    return 'job.posting.search';
  }

  public function newSearchEngine() {
    return new JobPostingSearchEngine();
  }

  public function getMethodSummary() {
    return pht('Read information about job.');
  }

}
