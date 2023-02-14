<?php

final class LobbyBreakMeTimeMenuItem
  extends LobbyBreakMenuItem {

  const MENUITEMKEY = 'lobby.break-me-time';

  public function getMenuItemTypeName() {
    return pht('Lobby Break Me Time');
  }

  private function getDefaultName() {
    return pht('Lobby Break Me Time');
  }

  public function canHideMenuItem(
    PhabricatorProfileMenuItemConfiguration $config) {
    return false;
  }

  protected function getConst() {
    return LobbyState::STATUS_BREAK_ME_TIME;
  }

}
