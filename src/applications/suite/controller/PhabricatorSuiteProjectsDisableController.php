<?php

final class PhabricatorSuiteProjectsDisableController
  extends PhabricatorSuiteController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {
    $viewer = $this->getViewer();
    $id = $request->getURIData('id');
    $via = $request->getURIData('via');

    $project = id(new PhabricatorProjectQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->executeOne();
    if (!$project) {
      return new Aphront404Response();
    }

    $is_disapprove = ($via == 'disapprove');
    $actor = $viewer;
    $done_uri = $this->getApplicationURI('projects');
    $should_disable = $project->getIsForRsp();

    if ($request->isFormPost()) {
      $xactions = array();

      $xactions[] = id(new PhabricatorProjectTransaction())
        ->setTransactionType(
          PhabricatorProjectIsForRspTransaction::TRANSACTIONTYPE)
        ->setNewValue(!$should_disable);

      id(new PhabricatorProjectTransactionEditor())
        ->setActor($actor)
        ->setActingAsPHID($viewer->getPHID())
        ->setContentSourceFromRequest($request)
        ->setContinueOnMissingFields(true)
        ->setContinueOnNoEffect(true)
        ->applyTransactions($project, $xactions);

      return id(new AphrontRedirectResponse())->setURI($done_uri);
    }

    if ($should_disable) {
      $title = pht('Disable RSP?');
      $short_title = pht('Disable RSP');

      $body = pht(
        'Disable RSP for %s? This project will no longer displayed on Suite.',
        phutil_tag('strong', array(), $project->getName()));

      $submit = pht('Disable RSP');
    } else {
      $title = pht('Enable RSP?');
      $short_title = pht('Enable RSP');

      $body = pht(
        'Enable RSP for %s? This project will be displayed on Suite.',
        phutil_tag('strong', array(), $project->getName()));

      $submit = pht('Enable RSP');
    }

    return $this->newDialog()
      ->setTitle($title)
      ->setShortTitle($short_title)
      ->appendParagraph($body)
      ->addCancelButton($done_uri)
      ->addSubmitButton($submit);
  }

  protected function requiresManageBilingCapability() {
    return true;
  }

  protected function requiresManageSubscriptionCapability() {
    return false;
  }

  protected function requiresManageUserCapability() {
    return false;
  }

}
