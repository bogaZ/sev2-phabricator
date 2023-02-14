<?php

final class LobbyBreakPrayMenuItem
  extends LobbyBreakMenuItem {

  const MENUITEMKEY = 'lobby.break-pray';

  public function getMenuItemTypeName() {
    return pht('Lobby Break Pray');
  }

  private function getDefaultName() {
    return pht('Lobby Break Pray');
  }

  public function canHideMenuItem(
    PhabricatorProfileMenuItemConfiguration $config) {
    return false;
  }

  protected function getConst() {
    return LobbyState::STATUS_BREAK_PRAY;
  }

}
