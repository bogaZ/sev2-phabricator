<?php

final class PhabricatorHomeMenuItemController
  extends PhabricatorHomeController {

  public function shouldRequireLogin() {
    return false;
  }

  public function shouldAllowPublic() {
    return true;
  }

  public function isGlobalDragAndDropUploadEnabled() {
    return true;
  }

  public function handleRequest(AphrontRequest $request) {
    $viewer = $this->getViewer();

    if (!$viewer->isLoggedIn()) {
      $controller = id(new LobbyPublicController());
      return $this->delegateToController($controller);
    }

    // Test if we should show mobile users the menu or the page content:
    // if you visit "/", you just get the menu. If you visit "/home/", you
    // get the content.
    $is_content = $request->getURIData('content');

    $application = 'PhabricatorHomeApplication';
    $home_app = id(new PhabricatorApplicationQuery())
      ->setViewer($viewer)
      ->withClasses(array($application))
      ->withInstalled(true)
      ->executeOne();

    $engine = id(new PhabricatorHomeProfileMenuEngine())
      ->setProfileObject($home_app)
      ->setCustomPHID($viewer->getPHID())
      ->setController($this)
      ->setShowContentCrumbs(false);

    $page = $engine->buildResponse();

    $navigation = $page->getNavigation();
    $navigation->appendChild($this->buildDarkModeToggle());

    return $page;
  }

  protected function buildDarkModeToggle() {
    require_celerity_resource('dark-mode-toggle-css');

    Javelin::initBehavior(
      'dark-mode-toggle',
      array('uri' => '/settings/adjust/')
    );

    $viewer = $this->getViewer();
    $preferences = PhabricatorUserPreferences::loadUserPreferences($viewer);
    $current_theme = $preferences->getPreference('resource-postprocessor');
    $checked = $current_theme == 'darkmode';

    $toggle = array(
      'type' => 'checkbox',
      'id' => 'theme-switch',
    );
    if ($checked) {
      $toggle['checked'] = true;
    }

    $switch = phutil_tag('div', array(
      'class' => 'theme-switch',
      'for' => 'checkbox',
    ), array(
      phutil_tag('input', $toggle),
      phutil_tag('div', array(
        'class'=>'slider round',
        'data-sigil' => 'theme-switch')),
    ));


    $indicator = phutil_tag('div', array(
      'id' => 'theme-indicator-container',
      'class' => 'theme-switch-indicator',
    ), phutil_tag('span', array(
      'id' => 'theme-indicator',
      'class' => 'visual-only phui-icon-view phui-font-fa fa-sun-o',
    )));

    $wrapper = phutil_tag('div', array(
      'id' => 'theme-switch-wrapper',
      'class' => 'theme-switch-wrapper'
    ), array(
      $switch, $indicator
    ));


    return phutil_tag('div', array(
      'id' => 'dark-mode-toggle-container',
      'class' => 'dark-mode-toggle-container'
    ), array(
      $wrapper,
    ));
  }

}
