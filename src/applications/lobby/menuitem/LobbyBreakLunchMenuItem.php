<?php

final class LobbyBreakLunchMenuItem
  extends LobbyBreakMenuItem {

  const MENUITEMKEY = 'lobby.break-lunch';

  public function getMenuItemTypeName() {
    return pht('Lobby Break Lunch');
  }

  private function getDefaultName() {
    return pht('Lobby Break Lunch');
  }

  public function canHideMenuItem(
    PhabricatorProfileMenuItemConfiguration $config) {
    return false;
  }

  protected function getConst() {
    return LobbyState::STATUS_BREAK_LUNCH;
  }

}
