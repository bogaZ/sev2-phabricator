<?php

final class PhabricatorSuiteCheckpointController extends PhabricatorController {

  public function shouldAllowPublic() {
    return true;
  }

 public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();

    $title = pht('Checkpoint');
    $warning = id(new PHUIActionPanelView())
      ->setIcon('fa-lock')
      ->setHeader(pht('Enforced organization policy'))
      ->setHref('#')
      ->setSubHeader(pht('Your email xxx%s is not part of our organization.', substr($viewer->loadPrimaryEmailAddress(),3)))
      ->setState(PHUIActionPanelView::COLOR_YELLOW);

    $header = id(new PHUIHeaderView())
      ->setHeader($title);

    $view = id(new PHUITwoColumnView())
      ->setHeader($header)
      ->setFooter(
        array(
          $warning,
        ));


    return $this->newSuitePage()
      ->setTitle($title)
      ->appendChild($view);

  }

}
