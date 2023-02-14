<?php

final class SuiteStatisticSearchEngine
  extends PhabricatorApplicationSearchEngine {

  public function getResultTypeDescription() {
    return pht('Suite Statistics');
  }

  public function getApplicationClassName() {
    return 'PhabricatorSuiteApplication';
  }

  public function newQuery() {
    return id(new SuiteStatisticQuery());
  }

  protected function buildCustomSearchFields() {
    return array();
  }

  protected function getDefaultFieldOrder() {
    return array();
  }

  protected function buildQueryFromParameters(array $map) {
    $query = $this->newQuery();

    $viewer = $this->requireViewer();

    return $query;
  }

  protected function getURI($path) {
    return '/suite/';
  }

  protected function getBuiltinQueryNames() {
    $names = array(
      'all' => pht('All'),
    );

    return $names;
  }

  public function buildSavedQueryFromBuiltin($query_key) {
    $query = $this->newSavedQuery();
    $query->setQueryKey($query_key);

    switch ($query_key) {
      case 'all':
        return $query;
    }

    return parent::buildSavedQueryFromBuiltin($query_key);
  }

  protected function renderResultList(
    array $results,
    PhabricatorSavedQuery $query,
    array $handles) {
    return SuiteStatistic::buildDashboardStats(
      PhabricatorUser::getOmnipotentUser());
  }

}
