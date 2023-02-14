<?php

final class PhabricatorSev2RolesController
  extends PhabricatorPeopleController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $this->getViewer();
    $id = $request->getURIData('id');
    $key_role = $request->getURIData('key');

    $user = id(new PhabricatorPeopleQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->executeOne();
    if (!$user) {
      return new Aphront404Response();
    }


    if ($user->getCustomRoles() == null) {
        $string = '[]';
      } else {
        $string = $user->getCustomRoles();
      }

    $obj = json_decode($string);
    $cond = true;
    if (in_array($key_role, $obj)) {
        array_splice($obj, array_search($key_role, $obj), 1);
    } else {
        array_push($obj, $key_role);
        $cond = false;
    }
    $done_uri = $this->getApplicationURI("manage/{$id}/");
    $validation_exception = null;
    if ($request->isFormOrHisecPost()) {
      $xactions = array();
      $xactions[] = id(new PhabricatorUserTransaction())
        ->setTransactionType(
            PhabricatorSev2RolesTransaction::TRANSACTIONTYPE)
        ->setNewValue(json_encode($obj));

      $editor = id(new PhabricatorUserTransactionEditor())
        ->setActor($viewer)
        ->setContentSourceFromRequest($request)
        ->setContinueOnMissingFields(true)
        ->setCancelURI($done_uri);

      try {
        $editor->applyTransactions($user, $xactions);
        return id(new AphrontRedirectResponse())->setURI($done_uri);
      } catch (PhabricatorApplicationTransactionValidationException $ex) {
        $validation_exception = $ex;
      }
    }

    if ($cond == true) {
      $title = pht('Remove as %s?', $key_role);
      $short = pht('Remove %s', $key_role);
      $body = pht(
        'Remove %s as an %s? They will no longer be able to '.
        'perform %s functions on %s install.',
        phutil_tag('strong', array(),
        $user->getUsername()), $key_role, $key_role, 'sev2');
      $submit = pht('Remove %s', $key_role);
    } else {
      $title = pht('Make %s?', $key_role);
      $short = pht('Make %s', $key_role);
      $body = pht(
        'Empower %s as an %s? They will be able to do power as roles',
        phutil_tag('strong', array(), $user->getUsername()), $key_role);
      $submit = pht('Make %s', $key_role);
    }

    return $this->newDialog()
      ->setValidationException($validation_exception)
      ->setTitle($title)
      ->setShortTitle($short)
      ->appendParagraph($body)
      ->addCancelButton($done_uri)
      ->addSubmitButton($submit);
  }

}
