<?php

final class PhabricatorCoursepathItemSearchConduitAPIMethod
  extends PhabricatorSearchEngineAPIMethod {

  public function getAPIMethodName() {
    return 'coursepath.items.search';
  }

  public function newSearchEngine() {
    return new CoursepathItemSearchEngine();
  }

  public function getMethodSummary() {
    return pht('Read information about course paths.');
  }

}
