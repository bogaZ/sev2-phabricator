<?php

final class PhabricatorSuiteProfileMenuEngine
  extends PhabricatorProfileMenuEngine {

  const ITEM_PICTURE = 'people.picture';
  const ITEM_PROFILE = 'people.details-from-suite';
  const ITEM_JOB_PROFILE = 'suite-profile.job-profile';
  const ITEM_SUBMISSIONS = 'suite-profile.coursepath-submission';
  const ITEM_MANAGE = 'suite-profile.manage';

  protected function isMenuEngineConfigurable() {
    return false;
  }

  public function getItemURI($path) {
    $user = $this->getProfileObject();
    $id = $user->getID();
    return "/suite/user/{$id}/item/{$path}";
  }

  protected function getBuiltinProfileItems($object) {
    $viewer = $this->getViewer();

    $items = array();

    $items[] = $this->newItem()
      ->setBuiltinKey(self::ITEM_PICTURE)
      ->setMenuItemKey(PhabricatorPeoplePictureProfileMenuItem::MENUITEMKEY);

    $items[] = $this->newItem()
      ->setBuiltinKey(self::ITEM_PROFILE)
      ->setMenuItemKey(SuitePeopleDetailsProfileMenuItem::MENUITEMKEY);

    $items[] = $this->newItem()
      ->setBuiltinKey(self::ITEM_JOB_PROFILE)
      ->setMenuItemKey(SuiteJobProfileMenuItem::MENUITEMKEY);


    $items[] = $this->newItem()
      ->setBuiltinKey(self::ITEM_SUBMISSIONS)
      ->setMenuItemKey(SuiteSubmissionProfileMenuItem::MENUITEMKEY);


    $items[] = $this->newItem()
      ->setBuiltinKey(self::ITEM_MANAGE)
      ->setMenuItemKey(SuiteManageProfileMenuItem::MENUITEMKEY);

    return $items;
  }

}
