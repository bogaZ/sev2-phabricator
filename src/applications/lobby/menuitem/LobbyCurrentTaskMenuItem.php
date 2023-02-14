<?php

final class LobbyCurrentTaskMenuItem
  extends PhabricatorProfileMenuItem {

  const MENUITEMKEY = 'lobby.switch-current-task';

  public function getMenuItemTypeName() {
    return pht('Set current task');
  }

  private function getDefaultName() {
    return pht('Set current task');
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

    $lobby = $config->getProfileObject();
    $lobby_phid = $lobby->getPHID();
    $text = $lobby->getCurrentTask();
    if (empty($text)) {
      $text = LobbyState::DEFAULT_TASK;
    }

    $item = $this->newItemView()
      ->setURI("/lobby/current/{$lobby_phid}/")
      ->setName($text)
      ->setIcon('fa-refresh');

    return array(
      $item,
    );
  }

}
