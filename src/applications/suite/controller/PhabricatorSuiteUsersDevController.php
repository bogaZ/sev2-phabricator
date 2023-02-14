<?php

final class PhabricatorSuiteUsersDevController
  extends PhabricatorSuiteController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {
    $viewer = $this->getViewer();
    $id = $request->getURIData('id');
    $via = $request->getURIData('via');

    $user = id(new PhabricatorPeopleQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->executeOne();
    if (!$user) {
      return new Aphront404Response();
    }

    // NOTE: We reach this controller via the administrative "Disable User"
    // on profiles and also via the "X" action on the approval queue. We do
    // things slightly differently depending on the context the actor is in.

    // In particular, disabling via "Disapprove" requires you be an
    // administrator (and bypasses the "Can Disable Users" permission).
    // Disabling via "Disable" requires the permission only.

    $is_disapprove = ($via == 'disapprove');
    $actor = $viewer;
    $done_uri = $this->getApplicationURI('users');
    $should_disable = !$user->getIsForDev();

    if ($viewer->getPHID() == $user->getPHID()) {
      return $this->newDialog()
        ->setTitle(pht('Something Stays Your Hand'))
        ->appendParagraph(
          pht(
            'Try as you might, you find you can not mark DEV to your own account.'))
        ->addCancelButton($done_uri, pht('Curses!'));
    }

    if ($request->isFormPost()) {
      $xactions = array();

      $xactions[] = id(new PhabricatorUserTransaction())
        ->setTransactionType(
          PhabricatorUserIsForDevTransaction::TRANSACTIONTYPE)
        ->setNewValue($should_disable);

      id(new PhabricatorUserTransactionEditor())
        ->setActor($actor)
        ->setActingAsPHID($viewer->getPHID())
        ->setContentSourceFromRequest($request)
        ->setContinueOnMissingFields(true)
        ->setContinueOnNoEffect(true)
        ->applyTransactions($user, $xactions);

      return id(new AphrontRedirectResponse())->setURI($done_uri);
    }

    if ($should_disable) {
      $title = pht('Test User?');
      $short_title = pht('Mark as Dev User');

      $body = pht(
        'Mark %s as test user?',
        phutil_tag('strong', array(), $user->getUsername()));

      $submit = pht('Update User');
    } else {
      $title = pht('Not Test User?');
      $short_title = pht('Mark as Non-Dev User');

      $body = pht(
        'Mark %s as non-test user?',
        phutil_tag('strong', array(), $user->getUsername()));

      $submit = pht('Update User');
    }

    return $this->newDialog()
      ->setTitle($title)
      ->setShortTitle($short_title)
      ->appendParagraph($body)
      ->addCancelButton($done_uri)
      ->addSubmitButton($submit);
  }

  protected function requiresManageBilingCapability() {
    return false;
  }

  protected function requiresManageSubscriptionCapability() {
    return false;
  }

  protected function requiresManageUserCapability() {
    return true;
  }

}
