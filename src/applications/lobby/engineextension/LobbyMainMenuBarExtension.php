<?php

final class LobbyMainMenuBarExtension
  extends PhabricatorMainMenuBarExtension {

  const MAINMENUBARKEY = 'lobby';

  public function isExtensionEnabledForViewer(PhabricatorUser $viewer) {
    return id(new Lobby())
                    ->setViewer($viewer)
                    ->allowedTojoin();
    // return PhabricatorApplication::isClassInstalledForViewer(
    //   'PhabricatorFavoritesApplication',
    //   $viewer);
  }

  public function getExtensionOrder() {
    return 1100;
  }

  public function buildMainMenus() {
    $viewer = $this->getViewer();
    $streams = array();
    $state_color = PHUIButtonView::RED;

    $lobby = LobbyState::getCurrent($viewer,
      PhabricatorContentSource::newForSource(
        LobbyContentSource::SOURCECONST),
      LobbyState::DEFAULT_DEVICE);

    $menu_text = $lobby->getCurrentTask();
    if (empty($menu_text)) {
      $menu_text = LobbyState::DEFAULT_TASK;
    } else {
      $state_color = PHUIButtonView::GREEN;
    }

    $dropdown = id(new PhabricatorActionListView())
      ->setViewer($viewer);

    $streams = $this->streamItems($viewer);

    $lobby_menus = $this->lobbyItems($viewer, $lobby);
    if (count($lobby_menus) > 0) {
      foreach($lobby_menus as $lobby_menu) {
        $dropdown->addAction($lobby_menu);
      }
    }

    $favorites = $this->favoritesItems($viewer);
    if (count($favorites) > 0) {
      foreach($favorites as $favorite) {
        $dropdown->addAction($favorite);
      }
    }

    $lobby_menu = id(new PHUIButtonView())
      ->setTag('a')
      ->setHref('#')
      ->setIcon('fa-circle '.$state_color)
      ->setText($menu_text)
      ->addClass('phabricator-core-user-menu')
      ->addClass('lobby-menu')
      ->setNoCSS(true)
      ->setDropdown(true)
      ->setDropdownMenu($dropdown)
      ->setAuralLabel($menu_text);

    if (count($streams) > 0) {
      $lobby_menu->addClass('alert-unread')
      ->setCounterId($lobby->getPHID())
      ->setCounter(count($streams));
    }

    return array(
      $lobby_menu,
    );
  }



  private function streamItems(PhabricatorUser $viewer) {
    // @NOTE : get all task that assigned with isStream flag
    return array();
  }

  private function lobbyItems(PhabricatorUser $viewer, LobbyState $lobby) {
    $menu_engine = id(new LobbyProfileMenuEngine())
      ->setViewer($viewer)
      ->setProfileObject($lobby)
      ->setCustomPHID($viewer->getPHID());

    $controller = $this->getController();
    if ($controller) {
      $menu_engine->setController($controller);
    }

    $filter_view = $menu_engine->newProfileMenuItemViewList()
      ->newNavigationView();

    $menu_view = $filter_view->getMenu();
    $item_views = $menu_view->getItems();

    $actions = array();
    foreach ($item_views as $item) {
      $action = id(new PhabricatorActionView())
        ->setName($item->getName())
        ->setHref($item->getHref())
        ->setIcon($item->getIcon())
        ->setType($item->getType())
        ->setWorkflow(true);
      $actions[] = $action;
    }

    return $actions;
  }

  private function favoritesItems(PhabricatorUser $viewer) {
    $applications = id(new PhabricatorApplicationQuery())
      ->setViewer($viewer)
      ->withClasses(array('PhabricatorFavoritesApplication'))
      ->withInstalled(true)
      ->execute();
    $favorites = head($applications);
    if (!$favorites) {
      return array();
    }

    $menu_engine = id(new PhabricatorFavoritesProfileMenuEngine())
      ->setViewer($viewer)
      ->setProfileObject($favorites)
      ->setCustomPHID($viewer->getPHID());

    $controller = $this->getController();
    if ($controller) {
      $menu_engine->setController($controller);
    }

    $filter_view = $menu_engine->newProfileMenuItemViewList()
      ->newNavigationView();

    $menu_view = $filter_view->getMenu();
    $item_views = $menu_view->getItems();

    $actions = array();
    foreach ($item_views as $item) {
      $action = id(new PhabricatorActionView())
        ->setName($item->getName())
        ->setHref($item->getHref())
        ->setIcon($item->getIcon())
        ->setType($item->getType());

      // NOTE: this is ugly hack, but we don't really have any choice
      if (strpos($item->getHref(), 'diffusion') === false &&
          strpos($item->getHref(), 'configure') === false) {
        // Except diffusion, set workflow
        $action->setWorkflow(true);
      }

      $actions[] = $action;
    }

    return $actions;
  }

}
