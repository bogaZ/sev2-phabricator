<?php

final class LobbyProfileMenuEngine
  extends PhabricatorProfileMenuEngine {

  const ITEM_CURRENT_TASK = 'lobby.current_task';
  const ITEM_BREAK_BATHROOM = 'lobby-break.bathroom';
  const ITEM_BREAK_LUNCH = 'lobby-break.lunch';
  const ITEM_BREAK_ME_TIME = 'lobby-break.me-time';
  const ITEM_BREAK_FAMILY = 'lobby-break.family';
  const ITEM_BREAK_PRAY = 'lobby-break.pray';
  const ITEM_BREAK_OTHER = 'lobby-break.other';

  protected function isMenuEngineConfigurable() {
    return false;
  }

  public function getItemURI($path) {
    return "/lobby/menu/{$path}";
  }

  protected function getBuiltinProfileItems($object) {

    assert_instances_of(array($object), 'LobbyState');

    $items = array();
    $viewer = $this->getViewer();

    // Build current state menu, as primary switcher
    $items[] = $this->newItem()
        ->setBuiltinKey(self::ITEM_CURRENT_TASK)
        ->setMenuItemKey(LobbyCurrentTaskMenuItem::MENUITEMKEY)
        ->setIsHeadItem(true);
    $items[] = $this->newItem()
        ->setBuiltinKey('head')
        ->setMenuItemKey(PhabricatorDividerProfileMenuItem::MENUITEMKEY)
        ->setIsHeadItem(true);


    // Build break menus
    foreach($this->getBreakMenuKeyMaps() as $builtin => $item_key) {
      $items[] = $this->newItem()
        ->setBuiltinKey($builtin)
        ->setMenuItemKey($item_key);
    }

    $items[] = $this->newDividerItem('tail');

    return $items;
  }

  protected function getBreakMenuKeyMaps() {
    return array(
      self::ITEM_BREAK_BATHROOM =>
        LobbyBreakBathroomMenuItem::MENUITEMKEY,
      self::ITEM_BREAK_LUNCH =>
        LobbyBreakLunchMenuItem::MENUITEMKEY,
      self::ITEM_BREAK_ME_TIME =>
        LobbyBreakMeTimeMenuItem::MENUITEMKEY,
      self::ITEM_BREAK_FAMILY =>
        LobbyBreakFamilyMenuItem::MENUITEMKEY,
      self::ITEM_BREAK_PRAY =>
        LobbyBreakPrayMenuItem::MENUITEMKEY,
      self::ITEM_BREAK_OTHER =>
        LobbyBreakOtherMenuItem::MENUITEMKEY,
    );
  }

}
