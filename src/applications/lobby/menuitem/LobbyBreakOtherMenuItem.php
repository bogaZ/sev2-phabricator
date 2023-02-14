<?php

final class LobbyBreakOtherMenuItem
  extends LobbyBreakMenuItem {

  const MENUITEMKEY = 'lobby.break-other';

  public function getMenuItemTypeName() {
    return pht('Lobby Break Other');
  }

  private function getDefaultName() {
    return pht('Lobby Break Other');
  }

  public function canHideMenuItem(
    PhabricatorProfileMenuItemConfiguration $config) {
    return false;
  }

  protected function getConst() {
    return LobbyState::STATUS_BREAK_OTHER;
  }

}
