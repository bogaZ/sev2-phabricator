<?php

final class PhabricatorJobPostingApplyController
  extends PhabricatorJobPostingController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $id = $request->getURIData('id');
    $xactions = array();

    $item = id(new JobPostingQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->requireCapabilities(
        array(
          PhabricatorPolicyCapability::CAN_EDIT,
          PhabricatorPolicyCapability::CAN_VIEW,
        ))
      ->needTechStack(true)
      ->executeOne();
    if (!$item) {
      return new Aphront404Response();
    }

    $view_uri = $this->getApplicationURI('view/'.$item->getID().'/applicants');

    if ($request->isFormPost()) {
      $applicant_phids = array();

      $add_applicants = $request->getArr('phids');
      if ($add_applicants) {
        foreach ($add_applicants as $phid) {
          $applicant_phids[] = $phid;
        }
      }

      $xactions[] = id(new JobPostingTransaction())
        ->setTransactionType(
          JobPostingInviteTransaction::TRANSACTIONTYPE)
        ->setNewValue($applicant_phids);

      $editor = id(new PhabricatorJobPostingEditor())
        ->setActor($viewer)
        ->setContentSourceFromRequest($request)
        ->setContinueOnNoEffect(true)
        ->setContinueOnMissingFields(true)
        ->applyTransactions($item, $xactions);

      return id(new AphrontRedirectResponse())
        ->setURI($view_uri);
    }

    $can_edit = PhabricatorPolicyFilter::hasCapability(
      $viewer,
      $item,
      PhabricatorPolicyCapability::CAN_EDIT);

    $form_box = null;
    $title = pht('Add Applicant');
    if ($can_edit) {
      $header_name = pht('Edit Applicants');

      $form = new AphrontFormView();
      $form
        ->setUser($viewer)
        ->setFullWidth(true)
        ->appendControl(
          id(new AphrontFormTokenizerControl())
            ->setName('phids')
            ->setLabel(pht('Invitees'))
            ->setDatasource(new PhabricatorPeopleDatasource()));
    }

    $dialog = id(new AphrontDialogView())
      ->setUser($viewer)
      ->setTitle(pht('Invite new applicant(s)'))
      ->appendForm($form)
      ->addCancelButton($view_uri)
      ->addSubmitButton(pht('Invite'));

    return $dialog;
  }

}
