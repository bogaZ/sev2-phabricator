<?php

final class PhabricatorCoursepathItemUnenrollController
  extends PhabricatorCoursepathItemController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $id = $request->getURIData('id');

    $item = id(new CoursepathItemQuery())
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

    $remove_phid = $request->getStr('phid');
    $view_uri = $this->getApplicationURI('item/view/'.$item->getID().'/registrars/');

    if ($request->isFormPost()) {
      $xactions = array();
      $xactions[] = id(new CoursepathTransaction())
        ->setTransactionType(
          CoursepathItemUnenrollTransaction::TRANSACTIONTYPE)
        ->setNewValue(array($remove_phid));

      $editor = id(new PhabricatorCoursepathItemEditor())
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
          'Really unenroll "%s" from %s?',
          phutil_tag('strong', array(), $item->getName()),
          phutil_tag('strong', array(), $handle->getName())))
      ->addCancelButton($view_uri)
      ->addSubmitButton(pht('Unenroll'));

    return $dialog;
  }

}
