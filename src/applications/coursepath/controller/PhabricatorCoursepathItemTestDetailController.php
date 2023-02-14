<?php

abstract class PhabricatorCoursepathItemTestDetailController
  extends PhabricatorController {

  private $item;
  private $itemTest;

  public function setItem(CoursepathItem $item) {
    $this->item = $item;
    return $this;
  }

  public function getItem() {
    return $this->item;
  }

  public function setItemTest(CoursepathItemTest $item_test) {
    $this->itemTest = $item_test;
    return $this;
  }

  public function getItemTest() {
    return $this->itemTest;
  }

  public function buildApplicationMenu() {
    return $this->buildSideNavView()->getMenu();
  }

  protected function buildHeaderView() {
    $viewer = $this->getViewer();
    $item = $this->getItem();
    $item_test = $this->getItemTest();

    $item_id = $item->getID();
    $item_test_id = $item_test->getID();

    if ($item_test->getStatus() == 'draft') {
      $status_icon = 'fa-ban';
      $status_color = 'dark';
    } else {
      $status_icon = 'fa-check';
      $status_color = 'bluegrey';
    }

    $status_name = idx(
      CoursepathItem::getStatusNameMap(),
      $item_test->getStatus());

    $path = id(new PHUITagView())
            ->setIcon('fa-road')
            ->setType(PHUITagView::TYPE_OBJECT)
            ->setName($item->getName())
            ->setHref(
                $this->getApplicationURI(
                  "/item/view/$item_id/"));

    $severity = id(new PHUITagView())
            ->setIcon('fa-hashtag')
            ->setColor($this->getSeverityColor($item_test->getSeverity()))
            ->setType(PHUITagView::TYPE_OBJECT)
            ->setName(ucwords($item_test->getSeverity()));

    $type = id(new PHUITagView())
            ->setIcon('fa-hashtag')
            ->setColor($this->getTypeColor($item_test->getType()))
            ->setType(PHUITagView::TYPE_OBJECT)
            ->setName(ucwords($item_test->getType()));

    $test_code = id(new PHUITagView())
                ->setIcon('fa-book')
                ->setType(PHUITagView::TYPE_OBJECT)
                ->setName(ucwords($item_test->getTestCode()));

    return id(new PHUIHeaderView())
      ->setHeader($item_test->getTitle())
      ->setUser($viewer)
      ->setPolicyObject($item_test)
      ->addTag($path)
      ->addTag($severity)
      ->addTag($type)
      ->addTag($test_code)
      ->setStatus($status_icon, $status_color, $status_name)
      ->setHeaderIcon('fa-road');
  }

  protected function buildApplicationCrumbs() {
    $item = $this->getItem();
    $item_test = $this->getItemTest();

    $item_id = $item->getID();
    $tes_id = $item_test->getID();

    $type = $item_test->getType();

    $all_test_uri = $this->getApplicationURI(
      "item/view/{$item_id}/tests?type=$type");
    $item_test_uri = $this->getApplicationURI(
        "/item/view/{$item_id}/tests/view/{$tes_id}");

    $crumbs = parent::buildApplicationCrumbs();
    $crumbs->addTextCrumb('All Tests', $all_test_uri);
    $crumbs->addTextCrumb($item_test->getTitle(), $item_test_uri);
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

    $nav->addLabel(pht('Skill Tests'));

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

  private function getSeverityColor($severity) {
    switch ($severity) {
      case 'basic':
        $color = 'blue';
        break;
      case 'medium':
        $color = 'green';
        break;
      case 'advance':
        $color = 'red';
        break;
      default:
        $color = 'blue';
        break;
    }
    return $color;
  }

  private function getTypeColor($severity) {
    switch ($severity) {
      case 'quiz':
        $color = 'yellow';
        break;
      case 'exercise':
        $color = 'red';
        break;
      default:
        $color = 'green';
        break;
    }
    return $color;
  }

}
