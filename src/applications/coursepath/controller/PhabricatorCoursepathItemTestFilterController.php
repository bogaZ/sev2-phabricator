<?php

abstract class PhabricatorCoursepathItemTestFilterController
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

    $can_edit = PhabricatorPolicyFilter::hasCapability(
      $viewer,
      $item,
      PhabricatorPolicyCapability::CAN_EDIT);

    $nav = id(new AphrontSideNavFilterView())
      ->setBaseURI(new PhutilURI($this->getApplicationURI()));

    $nav->addLabel(pht('Course Path'));

    $daily = CoursepathItemTest::TYPE_DAILY;
    $nav->addFilter(
      'daily',
      pht('Daily Skill Test'),
      $this->getApplicationURI("/item/view/{$id}/tests?type=$daily"));

    $quiz = CoursepathItemTest::TYPE_QUIZ;
    $nav->addFilter(
      'quiz',
      pht('Quiz Skill Test'),
      $this->getApplicationURI("/item/view/{$id}/tests?type=$quiz"));

    $exercise = CoursepathItemTest::TYPE_EXERCISE;
    $nav->addFilter(
      'exercise',
      pht('Exercise Skill Test'),
      $this->getApplicationURI("/item/view/{$id}/tests?type=$exercise"));

    $nav->selectFilter($filter);

    return $nav;
  }

}
