<?php

final class PhabricatorSuiteBalanceAddController
  extends PhabricatorSuiteController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {
    $viewer = $this->getViewer();
    $id = $request->getURIData('id');
    $via = $request->getURIData('via');

    $balance = id(new SuiteBalanceQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->executeOne();
    if (!$balance) {
      return new Aphront404Response();
    }

    $actor = $viewer;
    $done_uri = $this->getApplicationURI('balance/view/'.$id);

    if ($request->isFormPost()) {
      $is_withdrawable = $request->getBool('isWithdrawable');
      $addition = $request->getInt('addition');
      $substraction = $request->getInt('substraction');
      $remarks = $request->getStr('remarks');

      try {
        if ($addition) {
          $balance->add($actor, PhabricatorContentSource::newFromRequest(
            $request),
           $addition, $is_withdrawable, $remarks);
        } else if ($substraction) {
          $balance->sub($actor, PhabricatorContentSource::newFromRequest(
            $request),
            $substraction, $is_withdrawable, $remarks);
        }

        return id(new AphrontRedirectResponse())->setURI($done_uri);
      } catch (SuiteInvalidOperationException $ex) {
        return $this->newDialog()
          ->setTitle(pht('Something just out of math'))
          ->appendParagraph($ex->getMessage())
          ->addCancelButton($done_uri, pht('Cool!'));
      }
    }


    $form = id(new AphrontFormView())
      ->setUser($viewer)
      ->appendRemarkupInstructions(
        pht(
          'When balance is being added with withdrawable flag, user will '.
          'be able to request withdrawable to given amount.'))
      ->appendControl(
        id(new AphrontFormTextControl())
          ->setLabel(pht('Addition'))
          ->setName('addition'))
      ->appendControl(
        id(new AphrontFormTextControl())
          ->setLabel(pht('Substraction'))
          ->setName('substraction'))
      ->appendControl(
        id(new AphrontFormCheckboxControl())
          ->setLabel(pht('Type'))
          ->setName('isWithdrawable')
          ->setOptions(array(
            pht('Withdrawable?'),
          ))
          ->setValue(array())
          ->setName('isWithdrawable'))
            ->appendRemarkupInstructions(
        pht(
          'You may optionally customize the remarks of this balance '.
          'modification - which could be useful for auditing'))
      ->appendControl(
        id(new PhabricatorRemarkupControl())
          ->setLabel(pht('Remarks'))
          ->setName('remarks')
          ->setValue('Remarks on addition/substraction'));

    return $this->newDialog()
      ->setTitle(pht('%s Modifier', $balance->getMonogram()))
      ->appendForm($form)
      ->addCancelButton($done_uri)
      ->addSubmitButton(pht('Submit'));
  }

  protected function requiresManageBilingCapability() {
    return true;
  }

  protected function requiresManageSubscriptionCapability() {
    return true;
  }

  protected function requiresManageUserCapability() {
    return false;
  }

}
