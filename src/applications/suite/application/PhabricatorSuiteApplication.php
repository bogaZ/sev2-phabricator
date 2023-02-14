<?php

final class PhabricatorSuiteApplication extends PhabricatorApplication {

  public function getBaseURI() {
    return '/suite/';
  }

  public function getShortDescription() {
    return pht('Refactory Suite Extension');
  }

  public function getName() {
    return pht('Suite');
  }

  public function getIcon() {
    return 'fa-bolt';
  }

  public function getTitleGlyph() {
    return "\xE2\x8F\x9A";
  }

  public function getFlavorText() {
    return pht('Empowering people.');
  }

  public function getApplicationGroup() {
    return self::GROUP_ADMIN;
  }

  public function isPrototype() {
    return true;
  }

  public function getApplicationOrder() {
    return 0.10;
  }

  public function getRoutes() {
    return array(
      '/suite/' => array(
        '' => 'PhabricatorSuiteConsoleController',
        'start/' => 'PhabricatorSuiteLandingController',
        'checkpoint/' => 'PhabricatorSuiteCheckpointController',
        'auth/register/(?:(?P<akey>[^/]+)/)?' =>
          'PhabricatorSuiteAuthRegisterController',
        'auth/finish/' => 'PhabricatorSuiteAuthFinishController',

        // Legal proxy
        'legal/L(?P<id>\d+)' => 'PhabricatorSuiteLegalController',


        //
        'invites/' => 'PhabricatorSuiteInvitesController',
        'invites/sent' => 'PhabricatorSuiteInvitesSentController',

        // Projects
        $this->getQueryRoutePattern('projects/') =>
          'PhabricatorSuiteProjectsController',
          'projects/disable/(?:(?P<id>\d+)/)?' =>
            'PhabricatorSuiteProjectsDisableController',

        // Users
        $this->getQueryRoutePattern('users/') =>
          'PhabricatorSuiteUsersController',
        $this->getEditRoutePattern('users/edit/') =>
          'PhabricatorSuiteUsersEditController',
          'users/view/(?:(?P<id>\d+)/)?' =>
            'PhabricatorSuiteUsersViewController',
          'users/view/(?:(?P<id>\d+)/)cv' =>
            'PhabricatorSuiteUsersCvController',
          'users/view/(?:(?P<id>\d+)/)test-submission' =>
            'PhabricatorSuiteUsersSubmissionController',
          'users/disable/(?:(?P<id>\d+)/)?' =>
            'PhabricatorSuiteUsersDisableController',
          'users/dev/(?:(?P<id>\d+)/)?' =>
            'PhabricatorSuiteUsersDevController',

        // Transactions
        $this->getQueryRoutePattern('transactions/') =>
          'PhabricatorSuiteTransactionController',


        // Balance
        $this->getQueryRoutePattern('balance/') =>
          'PhabricatorSuiteBalanceController',
          'balance/view/(?:(?P<id>\d+)/)?' =>
            'PhabricatorSuiteBalanceViewController',
          'balance/(?:(?P<id>\d+)/)add' =>
            'PhabricatorSuiteBalanceAddController',

        // Subscriptions
        $this->getQueryRoutePattern('subscriptions/') =>
          'PhabricatorSuiteSubscriptionsController',
        $this->getEditRoutePattern('subscriptions/edit/') =>
          'PhabricatorSuiteSubscriptionsEditController',

        'transactions/test' => 'PhabricatorSuiteTransactionEditController',
      ),
    );
  }

  protected function getCustomCapabilities() {
    return array(
      PhabricatorSuiteCapabilityManageBilling::CAPABILITY => array(
        'default' => PhabricatorPolicies::POLICY_ADMIN,
        'template' => SuiteBalancePHIDType::TYPECONST,
      ),
      PhabricatorSuiteCapabilityManageSubscriptions::CAPABILITY => array(
        'default' => PhabricatorPolicies::POLICY_ADMIN,
        'template' => PhortuneSubscriptionPHIDType::TYPECONST,
      ),
      PhabricatorSuiteCapabilityManageUser::CAPABILITY => array(
        'default' => PhabricatorPolicies::POLICY_ADMIN,
        'template' => SuiteProfilePHIDType::TYPECONST,
      ),
    );
  }

}
