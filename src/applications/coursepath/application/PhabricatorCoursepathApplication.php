<?php

final class PhabricatorCoursepathApplication extends PhabricatorApplication
{
  public function getBaseURI()
  {
    return '/coursepath/';
  }

  public function getShortDescription()
  {
    return pht('Manage Course and Teachable integration');
  }

  public function getName()
  {
    return pht('Course');
  }

  public function getIcon()
  {
    return 'fa-road';
  }

  public function getTitleGlyph()
  {
    return "\xE2\x8F\x9A";
  }

  public function getFlavorText()
  {
    return pht('Learn something new.');
  }

  public function getApplicationGroup()
  {
    return self::GROUP_CORE;
  }

  public function isPrototype()
  {
    return true;
  }

  public function getApplicationOrder()
  {
    return 0.10;
  }

  public function getRoutes()
  {
    return array(
      '/coursepath/' => array(
        '' => 'PhabricatorCoursepathConsoleController',
        'teachable/' => array(
          $this->getQueryRoutePattern() => 'PhabricatorTeachableViewController',
          $this->getEditRoutePattern('edit/')
            => 'PhabricatorTeachableEditController',
        ),
        'item/' => array(
          $this->getQueryRoutePattern() => 'PhabricatorCoursepathItemListController',
          $this->getEditRoutePattern('edit/') => 'PhabricatorCoursepathItemEditController',
          'archive/(?:(?P<id>\d+)/)?'
                => 'PhabricatorCoursepathItemArchiveController',
          'tests/' => array(
            $this->getEditRoutePattern('edit/') =>
              'PhabricatorCoursepathItemTestEditController',
          ),
          'view/' => array(
            '(?P<id>[1-9]\d*)/'
              => 'PhabricatorCoursepathItemViewController',
            '(?P<id>[1-9]\d*)/stacks'
              => 'PhabricatorCoursepathItemStackController',
            '(?P<id>[1-9]\d*)/tests'
              => 'PhabricatorCoursepathItemTestController',
            '(?P<id>[1-9]\d*)/tests/view/' => array(
              '(?P<test_id>[1-9]\d*)/' =>
                'PhabricatorCoursepathItemTestViewController',
              '(?P<test_id>[1-9]\d*)/delete/' =>
                'PhabricatorCoursepathItemTestRemoveController',
              '(?P<test_id>[1-9]\d*)/archive/' =>
                'PhabricatorCoursepathItemTestArchiveController',
            ),
            '(?P<id>[1-9]\d*)/submissions/' => array(
              $this->getQueryRoutePattern() =>
                'PhabricatorCoursepathItemTestSubmissionListController',
            ),
            '(?P<id>[1-9]\d*)/submissions/view/' => array(
              '(?P<submit_id>[1-9]\d*)/score' =>
                'PhabricatorCoursepathSubmissionScoreController',
            ),
            '(?P<id>[1-9]\d*)/tracks/' => array(
              $this->getQueryRoutePattern() =>
                'PhabricatorCoursepathItemTrackListController',
            ),
            '(?P<id>[1-9]\d*)/tracks/view/' => array(
              '(?P<track_id>[1-9]\d*)/remove/' =>
                'PhabricatorCoursepathItemTrackRemoveController',
            ),
            '(?P<id>[1-9]\d*)/tracks/submit/'
              => 'PhabricatorCoursepathItemStackDialogController',
            '(?P<id>[1-9]\d*)/registrars/'
              => 'PhabricatorCoursepathItemRegistrarController',
            '(?P<id>[1-9]\d*)/registrars/enroll/'
              => 'PhabricatorCoursepathItemEnrollController',
            '(?P<id>[1-9]\d*)/registrars/unenroll/'
              => 'PhabricatorCoursepathItemUnenrollController',
            ),
        ),
      ),
    );
  }

  protected function getCustomCapabilities() {
    return array(
      CoursepathDefaultViewCapability::CAPABILITY => array(
        'caption' => pht('Default view policy for newly created paths.'),
        'template' => CoursepathItemPHIDType::TYPECONST,
        'capability' => PhabricatorPolicyCapability::CAN_VIEW,
      ),
      CoursepathDefaultEditCapability::CAPABILITY => array(
        'caption' => pht('Default edit policy for newly created paths.'),
        'template' => CoursepathItemPHIDType::TYPECONST,
        'capability' => PhabricatorPolicyCapability::CAN_EDIT,
      ),
      CoursepathManageCapability::CAPABILITY => array(),
    );
  }
}
