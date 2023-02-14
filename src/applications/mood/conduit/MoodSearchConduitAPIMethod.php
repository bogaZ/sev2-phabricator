<?php

final class MoodSearchConduitAPIMethod
  extends PhabricatorSearchEngineAPIMethod {

  public function getAPIMethodName() {
    return 'mood.search';
  }

  public function newSearchEngine() {
    return new PhabricatorMoodSearchEngine();
  }

  public function getMethodSummary() {
    return pht('Read information about moods user.');
  }
}
