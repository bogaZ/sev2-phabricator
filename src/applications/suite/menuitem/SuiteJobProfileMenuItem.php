<?php

final class SuiteJobProfileMenuItem
  extends PhabricatorProfileMenuItem {

  const MENUITEMKEY = 'suite-profile.job-profile';

  public function getMenuItemTypeName() {
    return pht('Job Profile');
  }

  private function getDefaultName() {
    return pht('Job Profile (CV)');
  }

  public function canHideMenuItem(
    PhabricatorProfileMenuItemConfiguration $config) {
    return false;
  }

  public function getDisplayName(
    PhabricatorProfileMenuItemConfiguration $config) {
    $name = $config->getMenuItemProperty('name');

    if (strlen($name)) {
      return $name;
    }

    return $this->getDefaultName();
  }

  public function buildEditEngineFields(
    PhabricatorProfileMenuItemConfiguration $config) {
    return array(
      id(new PhabricatorTextEditField())
        ->setKey('name')
        ->setLabel(pht('Name'))
        ->setPlaceholder($this->getDefaultName())
        ->setValue($config->getMenuItemProperty('name')),
    );
  }

  protected function newMenuItemViewList(
    PhabricatorProfileMenuItemConfiguration $config) {

    $user = $config->getProfileObject();
    $id = $user->getID();

    $item = $this->newItemView()
      ->setURI("/suite/users/view/{$id}/cv")
      ->setName($this->getDisplayName($config))
      ->setIcon('fa-list-alt');

    return array(
      $item,
    );
  }

}
