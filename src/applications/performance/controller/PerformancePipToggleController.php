<?php

final class PerformancePipToggleController
  extends PerformanceController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {
    $viewer = $this->getViewer();
    $id = $request->getURIData('id');
    $via = $request->getStr('via');

    $user = id(new PhabricatorPeopleQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->executeOne();
    if (!$user) {
      return new Aphront404Response();
    }

    $is_from_pip = ($via == 'pip');
    $actor = $viewer;
    $done_uri = $is_from_pip
                ? $this->getApplicationURI('pip')
                : $this->getApplicationURI('');

    if ($viewer->getPHID() == $user->getPHID()) {
      return $this->newDialog()
        ->setTitle(pht('Something Stays Your Hand'))
        ->appendParagraph(
          pht(
            'Try as you might, you find you can not disable your own account.'))
        ->addCancelButton($done_uri, pht('Curses!'));
    }


    $current_pip = id(new PerformancePipQuery())
                    ->setViewer($viewer)
                    ->withTargetPHIDs(array($user->getPHID()))
                    ->executeOne();

    if (!$current_pip) {
      // Create!
      $should_disable = false;
    } else {
      $should_disable = $current_pip->getIsActive();
    }

    if ($request->isFormPost()) {
      $xactions = array();

      if (!$current_pip) {
        PerformancePip::addNewTarget($viewer, $user,
        PhabricatorContentSource::newFromRequest($request));
      } else {
        $xactions[] = id(new PerformancePipTransaction())
          ->setTransactionType(
            PerformancePipIsActiveTransaction::TRANSACTIONTYPE)
          ->setNewValue(!$should_disable);

        id(new PerformancePipEditor())
          ->setActor($actor)
          ->setActingAsPHID($viewer->getPHID())
          ->setContentSourceFromRequest($request)
          ->setContinueOnMissingFields(true)
          ->setContinueOnNoEffect(true)
          ->applyTransactions($current_pip, $xactions);
      }

      return id(new AphrontRedirectResponse())->setURI($done_uri);
    }

    if ($should_disable) {
      $title = pht('Remove User from Performance Improvement Plan?');
      $short_title = pht('Remove User');

      $body = pht(
        'Remove %s? They will no longer considered as need improvement',
        phutil_tag('strong', array(), $user->getUsername()));

      $submit = pht('OK');
    } else {
      $title = pht('Add user to Performance Improvement Plan?');
      $short_title = pht('Add User');

      $body = pht(
        'Add %s? They will be considered as need improvement.',
        phutil_tag('strong', array(), $user->getUsername()));

      $submit = pht('Add User');
    }

    return $this->newDialog()
      ->setTitle($title)
      ->setShortTitle($short_title)
      ->addHiddenInput('via', $via)
      ->appendParagraph($body)
      ->addCancelButton($done_uri)
      ->addSubmitButton($submit);
  }

  protected function requiresManageCapability() {
    return true;
  }

  protected function requiresViewCapability() {
    return true;
  }

}
