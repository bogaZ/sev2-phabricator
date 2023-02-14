<?php

final class MentionSearchConduitAPIMethod
  extends PhabricatorSearchEngineAPIMethod {

  public function getAPIMethodName() {
    return 'mention.search';
  }

  public function newSearchEngine() {
    return new PhabricatorMentionSearchEngine();
  }

  public function getMethodSummary() {
    return pht('Read information about mention user.');
  }
}
