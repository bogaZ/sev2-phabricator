<?php

final class PhabricatorCoursepathItemTestRemoveController
  extends PhabricatorCoursepathController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $item_id = $request->getURIData('id');
    $test_id = $request->getURIData('test_id');

    $item_test = id(new CoursepathItemTestQuery())
      ->setViewer($viewer)
      ->withIDs(array($test_id))
      ->requireCapabilities(
        array(
          PhabricatorPolicyCapability::CAN_VIEW,
          PhabricatorPolicyCapability::CAN_EDIT,
        ))
      ->executeOne();
    if (!$item_test) {
      return new Aphront404Response();
    }

    $item = id(new CoursepathItemQuery())
      ->setViewer($viewer)
      ->withIDs(array($item_id))
      ->executeOne();
    if (!$item) {
      return new Aphront404Response();
    }

    $view_uri = $this->getApplicationURI(
      'item/view/'.$item_id.'/');

    if ($request->isFormPost()) {
      $xactions = array();
      $xactions[] = id(new CoursepathItemTestTransaction())
        ->setTransactionType(
          CoursepathItemTestRemoveTransaction::TRANSACTIONTYPE)
        ->setNewValue(array($test_id));

      $editor = id(new PhabricatorCoursepathItemTestEditor())
        ->setActor($viewer)
        ->setContentSourceFromRequest($request)
        ->setContinueOnNoEffect(true)
        ->setContinueOnMissingFields(true)
        ->applyTransactions($item_test, $xactions);

      return id(new AphrontRedirectResponse())
        ->setURI($view_uri);
    }

    $dialog = id(new AphrontDialogView())
      ->setUser($viewer)
      ->setTitle(pht('Confirm Removal'))
      ->appendParagraph(
        pht(
          'Really remove "%s" from %s?',
          phutil_tag('strong', array(), $item_test->getTitle()),
          phutil_tag('strong', array(), $item->getName())))
      ->addCancelButton($view_uri)
      ->addSubmitButton(pht('Remove'));

    return $dialog;
  }

}
