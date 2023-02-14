<?php

final class PhabricatorCoursepathItemArchiveController
  extends PhabricatorCoursepathController {

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

    $view_uri = $this->getApplicationURI('item/view/'.$item->getID().'/');

    if ($request->isFormPost()) {
      if ($item->isArchived()) {
        $new_status = CoursepathItem::STATUS_ACTIVE;
      } else {
        $new_status = CoursepathItem::STATUS_ARCHIVED;
      }

      $xactions = array();

      $xactions[] = id(new CoursepathTransaction())
        ->setTransactionType(
          CoursepathItemStatusTransaction::TRANSACTIONTYPE)
        ->setNewValue($new_status);

      id(new PhabricatorCoursepathItemEditor())
        ->setActor($viewer)
        ->setContentSourceFromRequest($request)
        ->setContinueOnNoEffect(true)
        ->setContinueOnMissingFields(true)
        ->applyTransactions($item, $xactions);

      return id(new AphrontRedirectResponse())->setURI($view_uri);
    }

    if ($item->isArchived()) {
      $title = pht('Activate Course Path');
      $body = pht('This Course Path will be re-commissioned into service.');
      $button = pht('Activate Course Path');
    } else {
      $title = pht('Archive Course Path');
      $body = pht(
        'This course path, '.
        'shall be immediately retired from service, and will be hidden from everyone. Are you sure?');
      $button = pht('Archive Course Path');
    }

    return $this->newDialog()
      ->setTitle($title)
      ->appendChild($body)
      ->addCancelButton($view_uri)
      ->addSubmitButton($button);
  }

}
