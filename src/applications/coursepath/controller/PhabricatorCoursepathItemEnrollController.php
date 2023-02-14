<?php

final class PhabricatorCoursepathItemEnrollController
  extends PhabricatorCoursepathItemController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $id = $request->getURIData('id');
    $xactions = array();

    $item = id(new CoursepathItemQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->requireCapabilities(
        array(
          PhabricatorPolicyCapability::CAN_EDIT,
          PhabricatorPolicyCapability::CAN_VIEW,
        ))
      ->executeOne();
    if (!$item) {
      return new Aphront404Response();
    }

    $view_uri = $this->getApplicationURI('item/view/'.$item->getID().'/registrars');

    if ($request->isFormPost()) {
      $enroll_phids = array();

      $add_registrars = $request->getArr('phids');
      if ($add_registrars) {
        foreach ($add_registrars as $phid) {
          $enroll_phids[] = $phid;
        }
      }

      $xactions[] = id(new CoursepathTransaction())
        ->setTransactionType(
          CoursepathItemEnrollTransaction::TRANSACTIONTYPE)
        ->setNewValue($enroll_phids);

      $editor = id(new PhabricatorCoursepathItemEditor())
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
    $title = pht('Add Registrar');
    if ($can_edit) {
      $header_name = pht('Edit Registrars');

      $form = new AphrontFormView();
      $form
        ->setUser($viewer)
        ->setFullWidth(true)
        ->appendControl(
          id(new AphrontFormTokenizerControl())
            ->setName('phids')
            ->setLabel(pht('Registrars'))
            ->setDatasource(new PhabricatorPeopleDatasource()));
    }

    $dialog = id(new AphrontDialogView())
      ->setUser($viewer)
      ->setTitle(pht('Add Registrars'))
      ->appendForm($form)
      ->addCancelButton($view_uri)
      ->addSubmitButton(pht('Add Registrars'));

    return $dialog;
  }

}
