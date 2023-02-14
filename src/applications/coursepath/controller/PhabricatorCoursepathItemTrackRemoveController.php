<?php

final class PhabricatorCoursepathItemTrackRemoveController
  extends PhabricatorCoursepathController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $item_id = $request->getURIData('id');
    $track_id = $request->getURIData('track_id');

    $track = id(new CoursepathItemTrackQuery())
      ->setViewer($viewer)
      ->withIDs(array($track_id))
      ->requireCapabilities(
        array(
          PhabricatorPolicyCapability::CAN_VIEW,
          PhabricatorPolicyCapability::CAN_EDIT,
        ))
      ->executeOne();
    if (!$track) {
      return new Aphront404Response();
    }

    $item = id(new CoursepathItemQuery())
      ->setViewer($viewer)
      ->withIDs(array($item_id))
      ->executeOne();
    if (!$item) {
      return new Aphront404Response();
    }

    $done_uri = $this->getApplicationURI(
      'item/view/'.$item_id.'/tracks');

    if ($request->isFormPost()) {
      $xactions = array();
      $xactions[] = id(new CoursepathItemTrackTransaction())
        ->setTransactionType(
            CoursepathItemTrackRemoveTransaction::TRANSACTIONTYPE)
        ->setNewValue(array($track_id));

      $editor = id(new PhabricatorCoursepathItemTrackEditor())
        ->setActor($viewer)
        ->setContentSourceFromRequest($request)
        ->setContinueOnNoEffect(true)
        ->setContinueOnMissingFields(true)
        ->applyTransactions($track, $xactions);

      return id(new AphrontRedirectResponse())
        ->setURI($done_uri);
    }

    $dialog = id(new AphrontDialogView())
      ->setUser($viewer)
      ->setTitle(pht('Confirm Removal'))
      ->appendParagraph(
        pht(
          'Really remove "%s" from %s?',
          phutil_tag('strong', array(), $track->getName()),
          phutil_tag('strong', array(), $item->getName())))
      ->addCancelButton($done_uri)
      ->addSubmitButton(pht('Remove'));

    return $dialog;
  }

}
