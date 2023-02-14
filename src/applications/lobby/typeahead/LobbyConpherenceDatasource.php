<?php

final class LobbyConpherenceDatasource
  extends PhabricatorTypeaheadDatasource {

  public function getBrowseTitle() {
    return pht('Browse Channel');
  }

  public function getPlaceholderText() {
    return pht('Type a channel name...');
  }

  public function getDatasourceApplicationClass() {
    return 'PhabricatorConpherenceApplication';
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

    $channels = id(new ConpherenceThread())->loadAll();
    foreach ($channels as $channel) {
      $key = $channel->getPHID();

      $result = id(new PhabricatorTypeaheadResult())
        ->setPHID($key)
        ->setName($channel->getTitle());

      $results[$key] = $result;
    }

    return $results;
  }

}
