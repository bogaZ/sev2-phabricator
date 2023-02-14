<?php

final class PhabricatorSuiteBalanceViewController
  extends PhabricatorSuiteController {

  private $balance;

  public function setBalance(SuiteBalance $balance) {
    $this->balance = $balance;
    return $this;
  }

  public function getBalance() {
    return $this->balance;
  }


  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $id = $request->getURIData('id');

    $balance = id(new SuiteBalanceQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->executeOne();

    if (!$balance) {
      return new Aphront404Response();
    }

    $this->setBalance($balance);


    $crumbs = $this->buildApplicationCrumbs();
    $title = $balance->loadUser()->getRealName();

    $header = $this->buildHeaderView();
    $modifier_action = $this->buildBalanceModifier();
    $header->addActionLink($modifier_action);

    $current = $this->buildCurrentBalance();

    $timeline = $this->buildTransactionTable($balance);

    $view = id(new PHUITwoColumnView())
      ->setHeader($header)
      ->setMainColumn(array(
          $current,
          $timeline,
        ));

    return $this->newPage()
      ->setTitle($title)
      ->setCrumbs($crumbs)
      ->setPageObjectPHIDs(array($balance->getPHID()))
      ->appendChild($view);
  }

  protected function buildHeaderView() {
    $balance = $this->getBalance();
    $viewer = $this->getViewer();
    $id = $balance->getID();

    $status_icon = 'fa-credit-card';
    $owner = $balance->loadUser();

    $header = id(new PHUIHeaderView())
      ->setHeader(pht('%s (%s)', $owner->getUsername(), $owner->getRealName()))
      ->setHeaderIcon($status_icon)
      ->setUser($viewer)
      ->setPolicyObject($balance);


    return $header;
  }

  protected function buildApplicationCrumbs() {
    $balance = $this->getBalance();
    $id = $balance->getID();
    $paths_uri = $this->getApplicationURI('/balance');
    $item_uri = $this->getApplicationURI("/balance/view/{$id}/");

    $crumbs = parent::buildApplicationCrumbs();
    $crumbs->addTextCrumb('Balance', $paths_uri);
    $crumbs->addTextCrumb($balance->getMonogram(), $item_uri);
    $crumbs->setBorder(true);
    return $crumbs;
  }

  protected function buildCurrentBalance() {
    $balance = $this->getBalance();
    $viewer = $this->getViewer();

    $view = id(new AphrontMultiColumnView())
              ->setFluidLayout(true);

    $credit = PhabricatorFile::loadBuiltin(
      $viewer, 'projects/v3/creditcard.png');
    $image = $credit->getBestURI();

    /* Action Panels */
    $total = id(new PHUIActionPanelView())
      ->setImage($image)
      ->setBigText(true)
      ->setHeader(pht('Cumulative Balance'))
      ->setHref('#')
      ->setSubHeader(PhortuneCurrency::newFromValueAndCurrency(
        ($balance->getAmount() + $balance->getWithdrawableAmount()),
        SuiteBalance::ACCEPTED_CURRENCY)->formatForDisplay())
      ->setState(PHUIActionPanelView::COLOR_BLUE);

    $view->addColumn($total);

    $withdrawable = id(new PHUIActionPanelView())
      ->setImage($image)
      ->setBigText(true)
      ->setHeader(pht('Withdrawable Balance'))
      ->setHref('#')
      ->setSubHeader($balance->getWithdrawableAmountAsCurrency()
      ->formatForDisplay())
      ->setState(PHUIActionPanelView::COLOR_GREEN);

    $view->addColumn($withdrawable);

    return $view;
  }

  protected function buildTransactionTable(SuiteBalance $balance) {

    $trans_dao = new SuiteBalanceTransaction();
    $transactions =  $trans_dao->loadAllWhere('objectPHID = %s',
      $balance->getPHID());

    $cart_phids = array_filter(
      array_keys(mpull($transactions, null, 'getCartPHID')));
    $carts = array();
    if (count($cart_phids) > 0) {
      $carts = id(new PhortuneCartQuery())
                  ->setViewer(PhabricatorUser::getOmnipotentUser())
                  ->needPurchases(true)
                  ->withPHIDs($cart_phids)
                  ->execute();
      $carts = mpull($carts, null, 'getPHID');
    }

    $rows = array();
    $total = array(
      'debit' => 0,
      'credit' => 0,
    );

    foreach ($transactions as $transaction) {
      $engine = PhabricatorMarkupEngine::getEngine()
                  ->setConfig('viewer', PhabricatorUser::getOmnipotentUser());
      $remarks = $engine->markupText($transaction->getRemarks());

      $related_order = null;

      $related_order = '-';
      if (($cart_phid = $transaction->getCartPHID())
          && count($carts) > 0
          && array_key_exists($cart_phid, $carts)) {
        $order = $carts[$cart_phid];
        $related_order = phutil_tag('a',
          array(
            'href' => $order->getDetailURI(),
          ),
          array($order->getName()));
      }

      $rows[] = array(
        phabricator_dual_datetime(
          $transaction->getDateCreated(),
          $this->getViewer()),
        $remarks,
        PhortuneCurrency::newFromValueAndCurrency(
          $transaction->getCreditAmount(), SuiteBalance::ACCEPTED_CURRENCY)
          ->formatForDisplay(),
        PhortuneCurrency::newFromValueAndCurrency(
          $transaction->getDebitAmount(), SuiteBalance::ACCEPTED_CURRENCY)
          ->formatForDisplay(),
        $transaction->getIsWithdrawable() ? 'Yes' : 'No',
        $related_order,
      );

      $total['debit'] += $transaction->getDebitAmount();
      $total['credit'] += $transaction->getCreditAmount();
    }

    // Add total row
    $rows[] = array(
      '',
      '',
      '',
      '',
      '',
      '',
    );
    $rows[] = array(
      '',
      'Subtotal',
      new PhutilSafeHTML('<strong>'.PhortuneCurrency::newFromValueAndCurrency(
        $total['credit'], SuiteBalance::ACCEPTED_CURRENCY)
        ->formatForDisplay().'</strong>'),
      new PhutilSafeHTML('<strong>'.PhortuneCurrency::newFromValueAndCurrency(
        $total['debit'], SuiteBalance::ACCEPTED_CURRENCY)
        ->formatForDisplay().'</strong>'),
      '',
      '',
    );

    $table = id(new AphrontTableView($rows))
      ->setNoDataString(pht('No balance transaction.'))
      ->setHeaders(
        array(
          pht('Date'),
          pht('Remarks'),
          pht('Credit Amount'),
          pht('Debit Amount'),
          pht('Is Withdrawable'),
          pht('Related Order'),
        ))
      ->setColumnClasses(
        array(
          'pri',
          ' ',
          'right',
          'right',
          'right',
          'right',
        ));

    $notice = pht('All balance transaction');
    $table->setNotice($notice);

    return $table;
  }

  protected function buildBalanceModifier() {
    $viewer = $this->getViewer();
    $balance = $this->getBalance();
    $id = $balance->getID();

    $can_edit = PhabricatorPolicyFilter::hasCapability(
      $viewer,
      $balance,
      PhabricatorPolicyCapability::CAN_EDIT);

    $modif_icon = 'fa-plus';
    $modif_text = pht('Add Balance');
    $modif_href = "/suite/balance/{$id}/add";

    $icon = id(new PHUIIconView())
      ->setIcon($modif_icon);

    return id(new PHUIButtonView())
      ->setTag('a')
      ->setWorkflow(true)
      ->setIcon($icon)
      ->setText($modif_text)
      ->setHref($modif_href)
      ->setDisabled(!$can_edit);
  }

  protected function requiresManageBilingCapability() {
    return true;
  }

  protected function requiresManageSubscriptionCapability() {
    return true;
  }

  protected function requiresManageUserCapability() {
    return false;
  }


}
