<?php

final class PhabricatorJobApplication extends PhabricatorApplication {

    public function getBaseURI() {
      return '/job/';
    }

    public function getShortDescription() {
      return pht('Manage job posting and applicants');
    }

    public function getName() {
      return pht('Job');
    }

    public function getIcon() {
      return 'fa-thumb-tack';
    }

    public function getTitleGlyph() {
      return "\xE2\x8C\xA8";
    }

    public function getFlavorText() {
      return pht('Because everyone needs a job');
    }

    public function getApplicationGroup() {
      return self::GROUP_CORE;
    }

    public function isPrototype() {
      return true;
    }

    public function getApplicationOrder() {
      return 0.8;
    }

    public function getRoutes() {
      return array(
        '/job/' => array(
          $this->getQueryRoutePattern() => 'PhabricatorJobPostingListController',
          $this->getEditRoutePattern('edit/') => 'PhabricatorJobPostingEditController',
          'view/' => array(
            '(?P<id>[1-9]\d*)/'
              => 'PhabricatorJobPostingViewController',
            '(?P<id>[1-9]\d*)/applicants/'
              => 'PhabricatorJobPostingApplicantController',
            '(?P<id>[1-9]\d*)/applicants/apply/'
              => 'PhabricatorJobPostingApplyController',
            '(?P<id>[1-9]\d*)/applicants/retract/'
              => 'PhabricatorJobPostingRetractController',
            ),
          'techstack/(?P<id>[1-9]\d*)/'
            => 'PhabricatorJobPostingTechStackController',
          'state/(?P<id>[1-9]\d*)/' => 'PhabricatorJobPostingStateController',
          'status/(?P<id>[1-9]\d*)/(?P<status>[^/]+)/' => 'PhabricatorJobPostingStatusController',
        ),
      );
    }

    protected function getCustomCapabilities() {
      return array(
        JobDefaultViewCapability::CAPABILITY => array(
          'caption' => pht('Default view policy.'),
          'template' => JobPostingPHIDType::TYPECONST,
          'capability' => PhabricatorPolicyCapability::CAN_VIEW,
        ),
        JobDefaultEditCapability::CAPABILITY => array(
          'caption' => pht('Default edit policy.'),
          'template' => JobPostingPHIDType::TYPECONST,
          'capability' => PhabricatorPolicyCapability::CAN_EDIT,
        ),
        JobManageCapability::CAPABILITY => array(),
      );
    }
}
