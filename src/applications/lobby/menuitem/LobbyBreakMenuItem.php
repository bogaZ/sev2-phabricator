<?php

abstract class LobbyBreakMenuItem
  extends PhabricatorProfileMenuItem {

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
        ->setValue($config->getMenuProperty('name')),
    );
  }

  protected function newMenuItemViewList(
    PhabricatorProfileMenuItemConfiguration $config) {

    $lobby = $config->getProfileObject();
    $lobby_phid = $lobby->getPHID();
    $const = $this->getConst();
    $text = $this->getLabel($const);
    $icon = $this->getIcon($const);

    $item = $this->newItemView()
      ->setURI("/lobby/break/{$lobby_phid}/{$const}/")
      ->setName($text)
      ->setIcon($icon);

    return array(
      $item,
    );
  }


  protected function getLabel($const) {
    $maps = LobbyState::getStatusMap();

    return isset($maps[$const])
          ? $maps[$const]
          : 'Unknown';
  }

  protected function getIcon($const) {
    $maps = LobbyState::getStatusIconMap();

    return isset($maps[$const])
          ? $maps[$const]
          : 'Unknown';
  }
}
