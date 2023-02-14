<?php

final class CoursepathItemDatasource
  extends PhabricatorTypeaheadDatasource {

  public function getBrowseTitle() {
    return pht('Browse Coursepath');
  }

  public function getPlaceholderText() {
    return pht('Type a course path name...');
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

    $items = id(new CoursepathItem())->loadAll();
    foreach ($items as $item) {
      $key = $item->getPHID();

      $result = id(new PhabricatorTypeaheadResult())
        ->setPHID($key)
        ->setName($item->getName());

      $results[$key] = $result;
    }

    return $results;
  }

}
