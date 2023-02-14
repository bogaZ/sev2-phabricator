<?php

final class LobbyBreakFamilyMenuItem
  extends LobbyBreakMenuItem {

  const MENUITEMKEY = 'lobby.break-family';

  public function getMenuItemTypeName() {
    return pht('Lobby Break Family');
  }

  private function getDefaultName() {
    return pht('Lobby Break Family');
  }

  public function canHideMenuItem(
    PhabricatorProfileMenuItemConfiguration $config) {
    return false;
  }

  protected function getConst() {
    return LobbyState::STATUS_BREAK_FAMILY;
  }

}
