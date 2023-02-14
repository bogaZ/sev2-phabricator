<?php

final class CoursepathItemTestListView extends AphrontView {

  private $item;
  private $type;
  private $itemTests;
  private $handles;

  public function setItem(CoursepathItem $item) {
    $this->item = $item;
    return $this;
  }

  public function setItemTests(array $item_tests) {
    $this->itemTests = $item_tests;
    return $this;
  }

  public function setHandles(array $handles) {
    $this->handles = $handles;
    return $this;
  }

  public function setType($type) {
    $this->type = $type;
    return $this;
  }

  public function render() {
    $viewer = $this->getViewer();

    $type = ucwords($this->type);
    $item = $this->item;
    $item_tests = mpull($this->itemTests, null, 'getPHID');

    $can_edit = PhabricatorPolicyFilter::hasCapability(
      $viewer,
      $item,
      PhabricatorPolicyCapability::CAN_EDIT);

    $item_phid = $item->getPHID();
    $baseuri = '/coursepath/item/tests/edit/form';
    $add_uri = "$baseuri/default?itemPHID=$item_phid&type=$this->type";
    $add_test_button = id(new PHUIButtonView())
      ->setTag('a')
      ->setIcon('fa-plus')
      ->setText(pht('Add %s Test', $type))
      ->setDisabled(!$can_edit)
      ->setHref(
        id(new PhutilURI(
          $add_uri
        )));

    $header = id(new PHUIHeaderView())
      ->setHeader(pht('%s Skill Tests', $type))
      ->addActionLink($add_test_button);

    $list = id(new PHUIObjectItemListView())
      ->setNoDataString(pht('This item does not have any tests.'))
      ->setFlush(true);

    foreach ($item_tests as $item_test) {
      $remove_uri = 'tests/view/'.$item_test->getID().'/delete/';

      $creator_handle = $viewer->renderHandle($item_test->getCreatorPHID());
      $create_date = phabricator_date($item_test->getDateCreated(), $viewer);
      $tutor_info = pht(
        'Created by %s on %s',
        $creator_handle->render(),
        $create_date);

      if ($item_test->getStack()) {
        $object = pht('[%s][%s][%s]',
          $item_test->getStack(),
          ucwords($item_test->getSeverity()),
          $item_test->getTestCode() ? $item_test->getTestCode() : '-');
      } else {
        $object = pht('[%s][%s]',
          ucwords($item_test->getSeverity()),
          $item_test->getTestCode() ? $item_test->getTestCode() : '-');
      }

      $test_id = $item_test->getID();
      $item = id(new PHUIObjectItemView())
        ->setHeader($item_test->getTitle())
        ->setSubhead($tutor_info)
        ->setObjectName($object)
        ->setHref(id(new PhutilURI("tests/view/$test_id")));

      if ($can_edit) {
        $item->addAction(
          id(new PHUIListItemView())
            ->setIcon('fa-times')
            ->setName(pht('Remove'))
            ->setHref($remove_uri)
            ->setWorkflow(true));
      }

      $list->addItem($item);
    }

    $box = id(new PHUIObjectBoxView())
      ->setHeader($header)
      ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
      ->setObjectList($list);

    return $box;
  }

}
