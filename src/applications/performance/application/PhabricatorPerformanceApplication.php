<?php

final class PhabricatorPerformanceApplication extends PhabricatorApplication {

  public function getName() {
    return pht('Performance');
  }

  public function getBaseURI() {
    return '/performance/';
  }

  public function getIcon() {
    return 'fa-child';
  }

  public function getShortDescription() {
    return pht('Key Performance Indicator');
  }

  public function getTitleGlyph() {
    return "\002a";
  }

  public function getApplicationGroup() {
    return self::GROUP_UTILITIES;
  }

  public function isPrototype() {
    return true;
  }

  public function getRoutes() {
    return array(
      '/performance/' => array(
        $this->getQueryRoutePattern('') => 'PerformanceHomeController',
        'pip/' => array(
          $this->getQueryRoutePattern('') => 'PerformancePipController',
          'toggle/(?:(?P<id>\d+)/)?' => 'PerformancePipToggleController',
        ),
        'worklog/' => array(
          $this->getQueryRoutePattern('') => 'PerformanceWorklogController',
        ),
        'progress/' => array(
          $this->getQueryRoutePattern('') => 'PerformanceProgressController',
        ),
        'whitelist/' => 'PerformanceWhitelistController',
        'view/' => array(
          'view/(?P<userPHID>\d+)/' => 'PerformanceViewController',
        ),
      ),
    );
  }

  protected function getCustomCapabilities() {
    return array(
      PerformanceManageCapability::CAPABILITY => array(
        'default' => PhabricatorPolicies::POLICY_ADMIN,
        'caption' => pht('Default manage policy for KPI.'),
      ),
    );
  }

}
