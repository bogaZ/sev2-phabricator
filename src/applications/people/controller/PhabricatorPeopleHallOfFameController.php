<?php

final class PhabricatorPeopleHallOfFameController
  extends PhabricatorPeopleController {

  public function shouldRequireAdmin() {
    return false;
  }

  public function handleRequest(AphrontRequest $request) {
   $controller = id(new PhabricatorApplicationSearchController())
      ->setQueryKey($request->getURIData('queryKey'))
      ->setSearchEngine(new PhabricatorPeopleUserHallOfFameSearchEngine())
      ->setNavigation($this->buildSideNavView());

    return $this->delegateToController($controller);
  }

  public function buildSideNavView($for_app = false) {
    $nav = new AphrontSideNavFilterView();
    $nav->setBaseURI(new PhutilURI($this->getApplicationURI()));

    $viewer = $this->getRequest()->getUser();

    id(new PhabricatorPeopleUserHallOfFameSearchEngine())
      ->setViewer($viewer)
      ->addNavigationItems($nav->getMenu());

    return $nav;
  }

}
