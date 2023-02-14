<?php

final class SuiteSubmissionProfileMenuItem
  extends PhabricatorProfileMenuItem {

  const MENUITEMKEY = 'suite-profile.coursepath-submission';

  public function getMenuItemTypeName() {
    return pht('Test Submission');
  }

  private function getDefaultName() {
    return pht('Test Skill Submission');
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
      ->setURI("/suite/users/view/{$id}/test-submission")
      ->setName($this->getDisplayName($config))
      ->setIcon('fa-file-code-o');

    return array(
      $item,
    );
  }

}
