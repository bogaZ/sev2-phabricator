<?php

final class PhabricatorTeachableViewController
  extends PhabricatorTeachableDetailController {

  public function shouldAllowPublic() {
    return true;
  }

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();

    $crumbs = $this->buildApplicationCrumbs();
    $crumbs->addTextCrumb(pht('Tech Stacks'));
    $crumbs->setBorder(true);
    $title = pht('Teachable Proxy');

    $header = $this->buildHeaderView();

    $stack_list = id(new PhabricatorTeachableListView())
      ->setUser($viewer);

    $view = id(new PHUITwoColumnView())
      ->setHeader($header)
      ->setFooter(array(
          $stack_list,
        ));

    $navigation = $this->buildSideNavView('Teachable Proxy');

    return $this->newPage()
      ->setTitle($title)
      ->setCrumbs($crumbs)
    //   ->setPageObjectPHIDs(array($stack->getPHID()))
      ->setNavigation($navigation)
      ->appendChild($view);
  }

}
