<?php

abstract class PhabricatorCoursepathItemDetailController
  extends PhabricatorController {

  private $item;

  public function setItem(CoursepathItem $item) {
    $this->item = $item;
    return $this;
  }

  public function getItem() {
    return $this->item;
  }

  public function buildApplicationMenu() {
    return $this->buildSideNavView()->getMenu();
  }

  protected function buildHeaderView() {
    $viewer = $this->getViewer();
    $item = $this->getItem();
    $id = $item->getID();

    if ($item->isArchived()) {
      $status_icon = 'fa-ban';
      $status_color = 'dark';
    } else {
      $status_icon = 'fa-check';
      $status_color = 'bluegrey';
    }
    $status_name = idx(
      CoursepathItem::getStatusNameMap(),
      $item->getStatus());

    return id(new PHUIHeaderView())
      ->setHeader($item->getName())
      ->setUser($viewer)
      ->setPolicyObject($item)
      ->setStatus($status_icon, $status_color, $status_name)
      ->setHeaderIcon('fa-road');
  }

  protected function buildApplicationCrumbs() {
    $item = $this->getItem();
    $id = $item->getID();
    $paths_uri = $this->getApplicationURI("/item/");
    $item_uri = $this->getApplicationURI("/item/view/{$id}/");

    $crumbs = parent::buildApplicationCrumbs();
    $crumbs->addTextCrumb("All Paths", $paths_uri);
    $crumbs->addTextCrumb($item->getName(), $item_uri);
    $crumbs->setBorder(true);
    return $crumbs;
  }

  protected function buildSideNavView($filter = null) {
    $viewer = $this->getViewer();
    $item = $this->getItem();
    $id = $item->getID();
    $phid = $item->getPHID();

    $can_edit = PhabricatorPolicyFilter::hasCapability(
      $viewer,
      $item,
      PhabricatorPolicyCapability::CAN_EDIT);

    $nav = id(new AphrontSideNavFilterView())
      ->setBaseURI(new PhutilURI($this->getApplicationURI()));

    $nav->addLabel(pht('Course Path'));

    $nav->addFilter(
      'view',
      pht('Path Detail'),
      $this->getApplicationURI("/item/view/{$id}/"));

    $nav->addFilter(
      'registrars',
      pht('Enrollment'),
      $this->getApplicationURI("/item/view/{$id}/registrars"));

    $daily = CoursepathItemTest::TYPE_DAILY;
    $nav->addFilter(
      'daily',
      pht('Skill Tests'),
      $this->getApplicationURI("/item/view/{$id}/tests?type=$daily"));

    $nav->addFilter(
      'submission',
      pht('Submissions'),
      $this->getApplicationURI("/item/view/{$id}/submissions"));

    $nav->addFilter(
      'teachable',
      pht('Teachable Courses'),
      $this->getApplicationURI("/item/view/{$id}/tracks"));

    $nav->selectFilter($filter);

    return $nav;
  }

}
