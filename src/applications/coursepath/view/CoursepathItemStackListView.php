<?php

final class CoursepathItemStackListView extends AphrontView {

  private $item;
  private $itemStacks;
  private $handles;

  public function setItem(CoursepathItem $item) {
    $this->item = $item;
    return $this;
  }

  public function setItemStacks(array $item_stacks) {
    $this->itemStacks = $item_stacks;
    return $this;
  }

  public function setHandles(array $handles) {
    $this->handles = $handles;
    return $this;
  }

  public function render() {
    $viewer = $this->getViewer();

    $item = $this->item;
    $item_stacks = mpull($this->itemStacks, null, 'getPHID');

    $can_edit = PhabricatorPolicyFilter::hasCapability(
      $viewer,
      $item,
      PhabricatorPolicyCapability::CAN_EDIT);

    $item_phid = $item->getPHID();
    $view_uri = "/coursepath/item/stacks/edit/form/default?itemPHID=$item_phid";
    $add_test_button = id(new PHUIButtonView())
      ->setTag('a')
      ->setIcon('fa-plus')
      ->setText(pht('Add Stack'))
      ->setDisabled(!$can_edit)
      ->setHref(
        id(new PhutilURI($view_uri)));

    $header = id(new PHUIHeaderView())
      ->setHeader(pht('Tech Stacks'))
      ->addActionLink($add_test_button);

    $list = id(new PHUIObjectItemListView())
      ->setNoDataString(pht('This item does not have any stacks.'))
      ->setFlush(true);

    foreach ($item_stacks as $item_stack) {
      $remove_uri = 'stacks/view/'.$item_stack->getID().'/delete/';

      $creator_handle = $viewer->renderHandle($item_stack->getCreatorPHID());
      $create_date = phabricator_date($item_stack->getDateCreated(), $viewer);
      $tutor_info = pht(
        'Created by %s on %s',
        $creator_handle->render(),
        $create_date);

      $stack_id = $item_stack->getID();
      $item = id(new PHUIObjectItemView())
        ->setHeader($item_stack->getName())
        ->setSubhead($tutor_info)
        ->setHref(id(new PhutilURI("stacks/view/$stack_id")));

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
