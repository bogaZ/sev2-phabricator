<?php

final class PhabricatorSuiteConsoleController
  extends PhabricatorSuiteController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {
    $viewer = $request->getViewer();

    $app = id(new PhabricatorApplicationQuery())
      ->setViewer($this->getViewer())
      ->withClasses(array('PhabricatorSuiteApplication'))
      ->executeOne();

    $can_manage_subscription = PhabricatorPolicyFilter::hasCapability(
            $viewer,
            $app,
            PhabricatorSuiteCapabilityManageSubscriptions::CAPABILITY);

    $can_manage_billing = PhabricatorPolicyFilter::hasCapability(
            $viewer,
            $app,
            PhabricatorSuiteCapabilityManageBilling::CAPABILITY);

    $menu = id(new PHUIObjectItemListView())
      ->setUser($viewer);

    $menu->addItem(
      id(new PHUIObjectItemView())
        ->setHeader(pht('Invites'))
        ->setHref($this->getApplicationURI('invites/'))
        ->setImageIcon('fa-paper-plane')
        ->addAttribute(pht('Invite Users to Suite.')));

    $menu->addItem(
      id(new PHUIObjectItemView())
        ->setHeader(pht('Users'))
        ->setHref($this->getApplicationURI('users/'))
        ->setImageIcon('fa-user-circle')
        ->addAttribute(pht('Manage Suite Users.')));

    if ($can_manage_billing) {
      $menu->addItem(
        id(new PHUIObjectItemView())
          ->setHeader(pht('Transactions'))
          ->setHref($this->getApplicationURI('transactions/'))
          ->setImageIcon('fa-exchange')
          ->addAttribute(pht('Review all transactions.')));

      $menu->addItem(
        id(new PHUIObjectItemView())
          ->setHeader(pht('Balance'))
          ->setHref($this->getApplicationURI('balance/'))
          ->setImageIcon('fa-credit-card')
          ->addAttribute(pht('Balance Management.')));

      $menu->addItem(
        id(new PHUIObjectItemView())
          ->setHeader(pht('Withdrawal'))
          ->setHref($this->getApplicationURI('withdrawals/'))
          ->setImageIcon('fa-handshake-o')
          ->addAttribute(pht('Manage RSP withdrawals.')));

      $menu->addItem(
        id(new PHUIObjectItemView())
          ->setHeader(pht('RSP Enabled Projects'))
          ->setHref($this->getApplicationURI('projects/'))
          ->setImageIcon('fa-suitcase')
          ->addAttribute(pht('Manage RSP projects.')));
    }

    if ($can_manage_subscription) {
      $menu->addItem(
        id(new PHUIObjectItemView())
          ->setHeader(pht('Subscriptions'))
          ->setHref($this->getApplicationURI('subscriptions/'))
          ->setImageIcon('fa-refresh')
          ->addAttribute(pht('Manage Suite Subscriptions.')));
    }


    $menu->addItem(
      id(new PHUIObjectItemView())
        ->setHeader(pht('Hiring'))
        ->setHref($this->getApplicationURI('jobs/'))
        ->setImageIcon('fa-building')
        ->addAttribute(pht('Manage hiring by Job posting.')));

    $crumbs = $this->buildApplicationCrumbs();
    $crumbs->addTextCrumb(pht('Console'));
    $crumbs->setBorder(true);

    $box = id(new PHUIObjectBoxView())
      ->setObjectList($menu);

    $header = id(new PHUIHeaderView())
      ->setHeader(pht('Suite Console'))
      ->setHeaderIcon('fa-bolt');

    $view = id(new PHUITwoColumnView())
      ->setHeader($header)
      ->setFooter(array(
        SuiteStatistic::buildAllStats($viewer),
        $box,
      ));

    return $this->newPage()
      ->setTitle(pht('Suite Console'))
      ->setCrumbs($crumbs)
      ->appendChild($view);
  }

  protected function requiresManageBilingCapability() {
    return false;
  }

  protected function requiresManageSubscriptionCapability() {
    return false;
  }

  protected function requiresManageUserCapability() {
    return true;
  }

}
