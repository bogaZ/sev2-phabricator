<?php

final class PhabricatorJobPostingRetractController
  extends PhabricatorJobPostingController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $id = $request->getURIData('id');

    $item = id(new JobPostingQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->needTechStack(true)
      ->requireCapabilities(
        array(
          PhabricatorPolicyCapability::CAN_VIEW,
          PhabricatorPolicyCapability::CAN_EDIT,
        ))
      ->executeOne();
    if (!$item) {
      return new Aphront404Response();
    }

    $remove_phid = $request->getStr('phid');
    $view_uri = $this->getApplicationURI('view/'.$item->getID().'/applicants');

    if ($request->isFormPost()) {
      $xactions = array();
      $xactions[] = id(new JobPostingTransaction())
        ->setTransactionType(
          JobPostingUninviteTransaction::TRANSACTIONTYPE)
        ->setNewValue(array($remove_phid));

      $editor = id(new PhabricatorJobPostingEditor())
        ->setActor($viewer)
        ->setContentSourceFromRequest($request)
        ->setContinueOnNoEffect(true)
        ->setContinueOnMissingFields(true)
        ->applyTransactions($item, $xactions);

      return id(new AphrontRedirectResponse())
        ->setURI($view_uri);
    }

    $handle = id(new PhabricatorHandleQuery())
      ->setViewer($viewer)
      ->withPHIDs(array($remove_phid))
      ->executeOne();

    $dialog = id(new AphrontDialogView())
      ->setUser($viewer)
      ->setTitle(pht('Confirm Removal'))
      ->appendParagraph(
        pht(
          'Really remove "%s" from %s?',
          phutil_tag('strong', array(), $handle->getName()),
          phutil_tag('strong', array(), $item->getName())
          )
        )
      ->addCancelButton($view_uri)
      ->addSubmitButton(pht('Remove'));

    return $dialog;
  }

}
