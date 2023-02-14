<?php

final class ConpherenceRestoreThreadConduitAPIMethod
  extends ConpherenceConduitAPIMethod {

  public function getAPIMethodName() {
    return 'conpherence.restorethread';
  }

  public function getMethodDescription() {
    return pht('Restore a chat room');
  }

  protected function defineParamTypes() {
    return array(
        'phid' => 'required phid',
    );
  }

  protected function defineReturnType() {
    return 'dict | null';
  }

  protected function defineErrorTypes() {
    return array(
      'ERR_NOT_FOUND'  => pht('PHID not found.'),
      'ERR_WRONG_USER' =>
        pht('Only admin or creator are be able to restore a chat room.'),
      'ERR_NEED_PARAM' => pht('Missing PHID as required parameter.'),
    );
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $request->getUser();
    $phid = $request->getValue('phid');

    if (!$phid) {
      return array(
        'message' => $this->getErrorDescription('ERR_NEED_PARAM'),
        'error' => true,
      );
    }

    $room = id(new ConpherenceThread())->loadOneWhere(
      'phid = %s', $phid);

    if (!$room) {
      return array(
        'message' => $this->getErrorDescription('ERR_NOT_FOUND'),
        'error' => true,
      );
    }

    $transaction = id(new ConpherenceTransaction())->loadOneWhere(
      'authorPHID = %s and objectPHID = %s and transactionType = %s',
      $viewer->getPHID(),
      $phid,
      PhabricatorCoreCreateTransaction::TRANSACTIONTYPE);

    $conpherence = id(new ConpherenceThreadQuery())
      ->setViewer($viewer)
      ->withPHIDs(array($phid))
      ->executeOne();

    $editor = id(new ConpherenceEditor())
      ->setContinueOnNoEffect(true)
      ->setContentSource($request->newContentSource())
      ->setActor($viewer);

    $xactions = array();
    $xactions[] = id(new ConpherenceTransaction())
    ->setTransactionType(ConpherenceThreadIsDeletedTransaction::TRANSACTIONTYPE)
    ->setNewValue(0);

    $xactions = $editor->applyTransactions($conpherence, $xactions);

    if ($viewer->getIsAdmin() || $transaction !== null) {
      $room->setIsDeleted(0);
      $room->save();
      return array(
        'message' => 'Successfully restore a chat room',
        'error' => false,
      );
    } else {
      return array(
        'message' => $this->getErrorDescription('ERR_WRONG_USER'),
        'error' => true,
      );
    }
  }
}
