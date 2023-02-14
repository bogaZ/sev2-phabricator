<?php

final class CoursepathItemTrackDatasource
  extends PhabricatorTypeaheadDatasource {

  public function getBrowseTitle() {
    return pht('Browse Teachable');
  }

  public function getPlaceholderText() {
    return pht('Type a course name...');
  }

  public function getDatasourceApplicationClass() {
    return 'PhabricatorCoursepathApplication';
  }

  public function loadResults() {
    $results = $this->buildResults();
    return $this->filterResultsAgainstTokens($results);
  }


  protected function renderSpecialTokens(array $values) {
    return $this->renderTokensFromResults($this->buildResults(), $values);
  }

  private function buildResults() {
    $results = array();

    $teachable = id(new TeachableFuture())
        ->setRawTeachableQuery('courses', array());

    $results = array();
    $titles = array();
    $course = array();

    $resources = $teachable->resolve();
    foreach ($resources as $resource) {
      foreach ($resource as $key => $data) {
        if (isset($data['name'])) {
          $course['name'] = $data['name'];
          $titles[] = $course;
        }
      }
    }

   return $titles;
    foreach ($titles as $key => $title) {
      $results[$key] = id(new PhabricatorTypeaheadResult())
          ->setName($title['name']);
    }

    return $results;
  }
}
