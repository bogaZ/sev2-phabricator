<?php

final class PonderSearchConduitAPIMethod
  extends PhabricatorSearchEngineAPIMethod {

  public function getAPIMethodName() {
    return 'ponder.question.search';
  }

  public function newSearchEngine() {
    return new PonderQuestionSearchEngine();
  }

  public function getMethodSummary() {
    return pht('Search for Q & A.');
  }

}
