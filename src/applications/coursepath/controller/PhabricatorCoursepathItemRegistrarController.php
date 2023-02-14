<?php

final class PhabricatorCoursepathItemRegistrarController
  extends PhabricatorCoursepathItemDetailController {

  public function shouldAllowPublic() {
    return true;
  }

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $id = $request->getURIData('id');

    $item = id(new CoursepathItemQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->executeOne();
    if (!$item) {
      return new Aphront404Response();
    }
    $this->setItem($item);

    $enrollments = id(new CoursepathItemEnrollmentQuery())
      ->setViewer($viewer)
      ->withItemPHIDs(array($item->getPHID()))
      ->execute();

    $registrar_phids = mpull($enrollments, 'getRegistrarPHID');
    $registrar_phids = array_reverse($registrar_phids);
    $handles = $this->loadViewerHandles($registrar_phids);

    $crumbs = $this->buildApplicationCrumbs();
    $crumbs->addTextCrumb(pht('Registrars'));
    $crumbs->setBorder(true);
    $title = $item->getName();

    $header = $this->buildHeaderView();

    $registrar_list = id(new CoursepathItemRegistrarListView())
      ->setItem($item)
      ->setEnrollments($enrollments)
      ->setHandles($handles)
      ->setUser($viewer);

    $view = id(new PHUITwoColumnView())
      ->setHeader($header)
      ->setFooter(array(
          $registrar_list,
        ));

    $navigation = $this->buildSideNavView('registrars');

    return $this->newPage()
      ->setTitle($title)
      ->setCrumbs($crumbs)
      ->setPageObjectPHIDs(array($item->getPHID()))
      ->setNavigation($navigation)
      ->appendChild($view);
  }

}
