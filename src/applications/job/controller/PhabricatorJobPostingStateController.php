<?php

final class PhabricatorJobPostingStateController
  extends PhabricatorJobPostingController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $id = $request->getURIData('id');

    $item = id(new JobPostingQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->requireCapabilities(
        array(
          PhabricatorPolicyCapability::CAN_VIEW,
          PhabricatorPolicyCapability::CAN_EDIT,
        ))
      ->executeOne();
    if (!$item) {
      return new Aphront404Response();
    }

    $view_uri = $this->getApplicationURI('/view/'.$id);
    if ($request->isFormPost()) {
      $xactions = array();
      $xactions[] = id(new JobPostingTransaction())
        ->setTransactionType(
          JobPostingCancelTransaction::TRANSACTIONTYPE)
        ->setNewValue($item->getIsCancelled() ? 0 : 1);

      $editor = id(new PhabricatorJobPostingEditor())
        ->setActor($viewer)
        ->setContentSourceFromRequest($request)
        ->setContinueOnNoEffect(true)
        ->setContinueOnMissingFields(true)
        ->applyTransactions($item, $xactions);

      return id(new AphrontRedirectResponse())
        ->setURI($view_uri);
    }


  $dialog = id(new AphrontDialogView())
    ->setUser($viewer)
    ->setTitle(pht('Confirm State Change'))
    ->appendParagraph(
      pht(
        $item->getIsCancelled() ? 'Activate %s back to the list?' : 'Really archive "%s" from the list?',
        phutil_tag('strong', array(), $item->getName())
      ))
    ->addCancelButton($view_uri)
    ->addSubmitButton(pht($item->getIsCancelled() ? 'Activate' : 'Archive'));

  return $dialog;
  }
}
