<?php

abstract class PhabricatorTeachableDetailController
  extends PhabricatorController {

  private $item;
  private $itemStack;

  public function setItem(CoursepathItem $item) {
    $this->item = $item;
    return $this;
  }

  public function getItem() {
    return $this->item;
  }

  public function getItemStack() {
    return $this->itemStack;
  }

  public function buildApplicationMenu() {
    return $this->buildSideNavView()->getMenu();
  }

  protected function buildHeaderView() {
    $viewer = $this->getViewer();

    return id(new PHUIHeaderView())
      ->setHeader(pht('Teachable Proxy Configuration'))
      ->setUser($viewer)
      ->setHeaderIcon('fa-exchange');
  }

  protected function buildApplicationCrumbs() {
    $paths_uri = $this->getApplicationURI('/teachable');

    $crumbs = parent::buildApplicationCrumbs();
    $crumbs->addTextCrumb('Teachable Proxy', $paths_uri);
    $crumbs->setBorder(true);
    return $crumbs;
  }

  protected function buildSideNavView($filter = null) {
    $nav = id(new AphrontSideNavFilterView())
      ->setBaseURI(new PhutilURI($this->getApplicationURI()));

    $nav->addLabel(pht('Teachable'));

    $nav->addFilter(
      'coursepath',
      pht('coursepath'),
      $this->getApplicationURI('/'),
      'fa-road');

    $nav->selectFilter($filter);

    return $nav;
  }

}
