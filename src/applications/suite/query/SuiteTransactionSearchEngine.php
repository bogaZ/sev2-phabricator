<?php

final class SuiteTransactionSearchEngine
  extends PhabricatorApplicationSearchEngine {

  private $merchant;
  private $account;
  private $subscription;

  public function newQuery() {
    return id(new PhortuneCartQuery())
      ->needPurchases(true);
  }

  public function canUseInPanelContext() {
    // These only make sense in an account or merchant context.
    return false;
  }

  public function setAccount(PhortuneAccount $account) {
    $this->account = $account;
    return $this;
  }

  public function getAccount() {
    return $this->account;
  }

  public function setMerchant(PhortuneMerchant $merchant) {
    $this->merchant = $merchant;
    return $this;
  }

  public function getMerchant() {
    return $this->merchant;
  }

  public function setSubscription(PhortuneSubscription $subscription) {
    $this->subscription = $subscription;
    return $this;
  }

  public function getSubscription() {
    return $this->subscription;
  }

  public function getResultTypeDescription() {
    return pht('Phortune Orders');
  }

  public function getApplicationClassName() {
    return 'PhabricatorSuiteApplication';
  }

  public function buildSavedQueryFromRequest(AphrontRequest $request) {
    $fields = $this->buildSearchFields();
    $viewer = $this->requireViewer();

    $saved = new PhabricatorSavedQuery();
    foreach ($fields as $field) {
      $field->setViewer($viewer);

      $value = $field->readValueFromRequest($request);
      $saved->setParameter($field->getKey(), $value);
    }

    return $saved;
  }

  public function buildQueryFromSavedQuery(PhabricatorSavedQuery $saved) {
    $viewer = $this->requireViewer();

    $map = array();
    $fields = $this->buildSearchFields();
    foreach ($fields as $field) {
      $field->setViewer($viewer);
      $field->readValueFromSavedQuery($saved);
      $value = $field->getValueForQuery($field->getValue());
      $map[$field->getKey()] = $value;
    }

    $saved->attachParameterMap($map);
    $query = $this->buildQueryFromParameters($map);

    $viewer = PhabricatorUser::getOmnipotentUser();

    $merchant = $this->getMerchant();
    $account = $this->getAccount();
    if ($merchant) {
      $query->withMerchantPHIDs(array($merchant->getPHID()));
    } else if ($account) {
      $query->withAccountPHIDs(array($account->getPHID()));
    } else {
      // We deliberately get all accounts
    }

    $subscription = $this->getSubscription();
    if ($subscription) {
      $query->withSubscriptionPHIDs(array($subscription->getPHID()));
    }


    if ($saved->getParameter('invoices')) {
      $query->withInvoices(true);
    } else {
      if ($map['cartStatus'] == null) {
        $query->withStatuses(
          array(
            PhortuneCart::STATUS_PURCHASING,
            PhortuneCart::STATUS_CHARGED,
            PhortuneCart::STATUS_HOLD,
            PhortuneCart::STATUS_REVIEW,
            PhortuneCart::STATUS_PURCHASED,
          ));
      }
    }

    return $query;
  }

  protected function buildCustomSearchFields() {
    $fields = array(
      id(new PhabricatorUsersSearchField())
        ->setLabel(pht('Billed users'))
        ->setKey('billedUserPHIDs')
        ->setDescription(
          pht('Search for order with given billed users.')),
      id(new PhabricatorSearchSelectField())
        ->setLabel(pht('Cart Status'))
        ->setKey('cartStatus')
        ->setOptions(PhortuneCart::getStatusNameMap())
        ->setDescription(
          pht(
            'Select specific cart status.')),
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

    $viewer = PhabricatorUser::getOmnipotentUser();

    // If the viewer can't browse the user directory, restrict the query to
    // just the user's own profile. This is a little bit silly, but serves to
    // restrict users from creating a dashboard panel which essentially just
    // contains a user directory anyway.
    $can_browse = PhabricatorPolicyFilter::hasCapability(
      $viewer,
      $this->getApplication(),
      PhabricatorSuiteCapabilityManageBilling::CAPABILITY);
    if (!$can_browse) {
      $query->withPHIDs(array('empty'));
    }

    if ($map['billedUserPHIDs']) {
      $user_phids = $map['billedUserPHIDs'];
      $user = id(new PhabricatorPeopleQuery())
                ->setViewer($viewer)
                ->withPHIDs($user_phids)
                ->executeOne();
      $accounts = PhortuneAccountQuery::loadAccountsForUser($user,
                  PhabricatorContentSource::newForSource(
                    SuiteContentSource::SOURCECONST));
      $account_phids = mpull($accounts, null, 'getPHID');
      if (!empty($account_phids)) {
        $query->withAccountPHIDs(array_keys($account_phids));
      }
    }

    if ($map['cartStatus']) {
      $query->withStatuses(array($map['cartStatus']));
    }

    return $query;
  }

  protected function getURI($path) {
    $merchant = $this->getMerchant();
    $account = $this->getAccount();
    if ($merchant) {
      return $merchant->getOrderListURI($path);
    } else if ($account) {
      return $account->getOrderListURI($path);
    } else {
      return '/suite/transactions/'.$path;
    }
  }

  protected function getBuiltinQueryNames() {
    $names = array(
      'all' => pht('Order History'),
      'invoices' => pht('Unpaid Invoices'),
    );

    return $names;
  }

  public function buildSavedQueryFromBuiltin($query_key) {

    $query = $this->newSavedQuery();
    $query->setQueryKey($query_key);

    switch ($query_key) {
      case 'all':
        return $query;
      case 'invoices':
        return $query->setParameter('invoices', true);
    }

    return parent::buildSavedQueryFromBuiltin($query_key);
  }

  protected function getRequiredHandlePHIDsForResultList(
    array $carts,
    PhabricatorSavedQuery $query) {
    $phids = array();
    foreach ($carts as $cart) {
      $phids[] = $cart->getPHID();
      $phids[] = $cart->getMerchantPHID();
      $phids[] = $cart->getAuthorPHID();
    }
    return $phids;
  }

  protected function renderResultList(
    array $carts,
    PhabricatorSavedQuery $query,
    array $handles) {
    assert_instances_of($carts, 'PhortuneCart');

    $viewer = $this->requireViewer();
    $all_billing_users = array('' => array('name' => 'All'));
    $selected_billing_user = $this->getRequest()
                                ->getStr('selectedBillingUserPHID', '');

    $rows = array();
    foreach ($carts as $cart) {
      if (!empty($selected_billing_user)
        && $selected_billing_user != $cart->getAuthorPHID()) {
          continue;
      }

      $merchant = $cart->getMerchant();

      $rows[] = array(
        $cart->getID(),
        $handles[$cart->getPHID()]->renderLink(),
        $handles[$merchant->getPHID()]->renderLink(),
        $handles[$cart->getAuthorPHID()]->renderLink(),
        $cart->getTotalPriceAsCurrency()->formatForDisplay(),
        PhortuneCart::getNameForStatus($cart->getStatus()),
        phabricator_datetime($cart->getDateModified(), $viewer),
      );

      $all_billing_users[$cart->getAuthorPHID()] = array(
        'name' => $handles[$cart->getAuthorPHID()]->getObjectName(),
      );
    }

    $table = id(new AphrontTableView($rows))
      ->setNoDataString(pht('No orders match the query.'))
      ->setHeaders(
        array(
          pht('ID'),
          pht('Order'),
          pht('Merchant'),
          pht('Authorized By'),
          pht('Amount'),
          pht('Status'),
          pht('Updated'),
        ))
      ->setColumnClasses(
        array(
          '',
          'pri',
          '',
          '',
          'wide right',
          '',
          'right',
        ));

    $merchant = $this->getMerchant();
    if ($merchant) {
      $notice = pht('Orders for %s', $merchant->getName());
    } else {
      $notice = pht('All Orders');
    }
    $table->setNotice($notice);

    $result = new PhabricatorApplicationSearchResultView();
    $result->setTable($table);

    $path = $this->getRequest()->getPath();

    if (strpos($path, 'invoices') !== false) {

      $selected = $this->getRequest()->getStr('selectedBillingUserPHID', '');
      $selected_option = idx($all_billing_users, $selected);

      $billing_users = id(new PHUIButtonView())
        ->setTag('a')
        ->setIcon('fa-user ')
        ->setText(pht('Billing User: %s', $selected_option['name']));

      $dropdown = id(new PhabricatorActionListView())
        ->setUser($viewer);

      foreach ($all_billing_users as $key => $option) {
        $uri = $path.'?selectedBillingUserPHID='.$key;

        $dropdown->addAction(
          id(new PhabricatorActionView())
            ->setName($option['name'])
            ->setHref($uri)
            ->setWorkflow(false));
      }

      $billing_users->setDropdownMenu($dropdown);
      $result->addAction($billing_users);

      $print_icon = id(new PHUIIconView())
        ->setIcon('fa-print');

      $print_btn = id(new PHUIButtonView())
        ->setTag('a')
        ->setWorkflow(false)
        ->setIcon($print_icon)
        ->setText('Printable Version')
        ->setHref('');
      $result->addAction($print_btn);
    }

    return $result;
  }
}
