<?php

final class PhabricatorTeachableListView extends AphrontView {

  public function render() {
    $viewer = $this->getViewer();

    $config = id(new TeachableConfigurationQuery())
      ->setViewer($viewer)
      ->executeOne();

    $header = id(new PHUIHeaderView())
      ->setHeader(pht('Configuration'));

    $list = id(new PHUIObjectItemListView())
      ->setNoDataString(pht('This item does not have any configurations.'))
      ->setFlush(true);

    $status = 'fa-check-circle green';
    if (!$config) {
      $status = 'fa-times-circle red';
    }

    $item = id(new PHUIObjectItemView())
    ->setHeader('Teachable')
    ->setStatusIcon($status)
    ->setHref(id(new PhutilURI('edit/form/default/')));

    $list->addItem($item);

    $box = id(new PHUIObjectBoxView())
      ->setHeader($header)
      ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
      ->setObjectList($list);

    return $box;
  }

}
