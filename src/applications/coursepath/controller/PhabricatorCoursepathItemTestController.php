<?php

final class PhabricatorCoursepathItemTestController
  extends PhabricatorCoursepathItemTestFilterController {

  public function shouldAllowPublic() {
    return true;
  }

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $id = $request->getURIData('id');
    $type = $request->getStr('type');

    $item = id(new CoursepathItemQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->executeOne();

    if (!$item) {
      return new Aphront404Response();
    }

    $this->setItem($item);

    $item_tests = id(new CoursepathItemTestQuery())
      ->setViewer($viewer)
      ->withTypes(array($type))
      ->withItemPHIDs(array($item->getPHID()))
      ->execute();

    $phids = mpull($item_tests, 'getPHID');
    $phids = array_reverse($phids);
    $handles = $this->loadViewerHandles($phids);

    $crumbs = $this->buildApplicationCrumbs();
    $crumbs->addTextCrumb(pht('Tests'));
    $crumbs->setBorder(true);
    $title = $item->getName();

    $header = $this->buildHeaderView();

    $registrar_list = id(new CoursepathItemTestListView())
      ->setType($type)
      ->setItem($item)
      ->setItemTests($item_tests)
      ->setHandles($handles)
      ->setUser($viewer);

    $view = id(new PHUITwoColumnView())
      ->setHeader($header)
      ->setFooter(array(
          $registrar_list,
        ));

    $navigation = $this->buildSideNavView('tests');

    return $this->newPage()
      ->setTitle($title)
      ->setCrumbs($crumbs)
      ->setPageObjectPHIDs(array($item->getPHID()))
      ->setNavigation($navigation)
      ->appendChild($view);
  }

}
