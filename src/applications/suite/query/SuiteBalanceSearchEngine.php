<?php

final class SuiteBalanceSearchEngine
  extends PhabricatorApplicationSearchEngine {

  public function getResultTypeDescription() {
    return pht('Balance');
  }

  public function getApplicationClassName() {
    return 'PhabricatorSuiteApplication';
  }

  public function newQuery() {
    return id(new SuiteBalanceQuery());
  }

  protected function buildCustomSearchFields() {
    $fields = array(
      id(new PhabricatorUsersSearchField())
        ->setLabel(pht('Balance users'))
        ->setKey('balanceUserPHIDs')
        ->setDescription(
          pht('Search for balance with given users.')),
    );

    return $fields;
  }

  protected function getDefaultFieldOrder() {
    return array(
      '...',
      'createdStart',
      'createdEnd',
    );
  }

  protected function buildQueryFromParameters(array $map) {
    $query = $this->newQuery();

    $viewer = $this->requireViewer();

    // If the viewer can't browse the user directory, restrict the query to
    // just the user's own profile. This is a little bit silly, but serves to
    // restrict users from creating a dashboard panel which essentially just
    // contains a user directory anyway.
    $can_browse = PhabricatorPolicyFilter::hasCapability(
      $viewer,
      $this->getApplication(),
      PhabricatorSuiteCapabilityManageBilling::CAPABILITY);
    if (!$can_browse) {
      $query->withPHIDs(array('undefined'));
    }

    if ($map['balanceUserPHIDs']) {
      $query->withOwnerPHIDs($map['balanceUserPHIDs']);
    }

    return $query;
  }

  protected function getURI($path) {
    return '/suite/balance/'.$path;
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
    array $balances,
    PhabricatorSavedQuery $query,
    array $handles) {
      assert_instances_of($balances, 'SuiteBalance');

      $viewer = $this->requireViewer();

      $rows = array();
      foreach ($balances as $balance) {
        $owner = $balance->loadUser();
        $account = $balance->loadAccount();

        $total = $balance->getAmount() + $balance->getWithdrawableAmount();
        $total_in_currency = PhortuneCurrency::newFromValueAndCurrency(
            $total, SuiteBalance::ACCEPTED_CURRENCY);

        $rows[] = array(
          $balance->getMonogram(),
          phutil_tag('a',
            array('href' => '/p/'.$owner->getUserName()),
            array($owner->getRealName())),
          phutil_tag('a',
            array('href' => $account->getURI()),
            array($account->getName())),
          $total_in_currency->formatForDisplay(),
          $balance->getWithdrawableAmountAsCurrency()
            ->formatForDisplay(),
          phabricator_datetime($balance->getDateModified(), $viewer),
          phutil_tag('a',
            array(
              'href' => $balance->getViewURI(),
              'class' => 'button has-icon phui-button-simple',
            ),
            array(
            phutil_tag(
              'span',
              array(
                'class' => 'visual-only phui-icon-view phui-font-fa fa fa-list',
              ),
              array('')),
              ' Details',
            )),
        );
      }

      $table = id(new AphrontTableView($rows))
        ->setNoDataString(pht('No balance match the query.'))
        ->setHeaders(
          array(
            pht('ID'),
            pht('User'),
            pht('Payment Account'),
            pht('Total Amount'),
            pht('Withdrawable Amount'),
            pht('Updated'),
            pht('Actions'),
          ))
        ->setColumnClasses(
          array(
            'pri',
            ' ',
            ' ',
            ' ',
            ' ',
            ' ',
            'right',
          ));

      $notice = pht('All generated balance');
      $table->setNotice($notice);

      $result = new PhabricatorApplicationSearchResultView();
      $result->setTable($table);

      return $result;
  }

}
