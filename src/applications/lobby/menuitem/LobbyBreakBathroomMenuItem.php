<?php

final class LobbyBreakBathroomMenuItem
  extends LobbyBreakMenuItem {

  const MENUITEMKEY = 'lobby.break-bathroom';

  public function getMenuItemTypeName() {
    return pht('Lobby Break Bathroom');
  }

  private function getDefaultName() {
    return pht('Lobby Break Bathroom');
  }

  public function canHideMenuItem(
    PhabricatorProfileMenuItemConfiguration $config) {
    return false;
  }

  protected function getConst() {
    return LobbyState::STATUS_BREAK_BATHROOM;
  }

}
