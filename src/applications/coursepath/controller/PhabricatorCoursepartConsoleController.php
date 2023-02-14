<?php

final class PhabricatorCoursepathConsoleController extends PhabricatorCoursepathController {

  public function shouldAllowPublic() {
    return true;
  }

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();

    $menu = id(new PHUIObjectItemListView())
      ->setUser($viewer);

    $menu->addItem(
      id(new PHUIObjectItemView())
        ->setHeader(pht('Course Path'))
        ->setHref($this->getApplicationURI('item/'))
        ->setImageIcon('fa-clone')
        ->addAttribute(pht('Manage Course path.')));

    $menu->addItem(
      id(new PHUIObjectItemView())
        ->setHeader(pht('Teachable Proxy'))
        ->setHref($this->getApplicationURI('teachable/'))
        ->setImageIcon('fa-exchange')
        ->addAttribute(pht('Manage Teachable proxy.')));

    $crumbs = $this->buildApplicationCrumbs();
    $crumbs->addTextCrumb(pht('Console'));
    $crumbs->setBorder(true);

    $box = id(new PHUIObjectBoxView())
      ->setObjectList($menu);

    $header = id(new PHUIHeaderView())
      ->setHeader(pht('Course Console'))
      ->setHeaderIcon('fa-road');

    $view = id(new PHUITwoColumnView())
      ->setHeader($header)
      ->setFooter(array(
        $box,
      ));

    return $this->newPage()
      ->setTitle(pht('Course Console'))
      ->setCrumbs($crumbs)
      ->appendChild($view);
  }

}
