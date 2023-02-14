<?php

final class ConpherenceDeleteThreadConduitAPIMethod
  extends ConpherenceConduitAPIMethod {

  public function getAPIMethodName() {
    return 'conpherence.deletethread';
  }

  public function getMethodDescription() {
    return pht('Delete a room chat');
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
      'ERR_NOT_FOUND'  => pht('Failed to delete, this phid not found.'),
      'ERR_WRONG_USER' => pht('You are not the creator of this room message.'),
      'ERR_NEED_PARAM' => pht('Must pass an phid.'),
      'ERR_DELETED' => pht('Failed to delete, this phid already deleted.'),
    );
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $request->getUser();
    $phid = $request->getValue('phid');

    if (!$phid) {
      throw new ConduitException('ERR_NEED_PARAM');
    }

    $conpherence = id(new ConpherenceThreadQuery())
      ->setViewer($viewer)
      ->withPHIDs(array($phid))
      ->executeOne();

      if (!$conpherence) {
        throw new ConduitException('ERR_NOT_FOUND');
      } else if ($conpherence->getIsDeleted()) {
        throw new ConduitException('ERR_DELETED');
      }

    $editor = id(new ConpherenceEditor())
      ->setContinueOnNoEffect(true)
      ->setContentSource($request->newContentSource())
      ->setActor($viewer);

    $xactions = array();
    $xactions[] = id(new ConpherenceTransaction())
    ->setTransactionType(ConpherenceThreadIsDeletedTransaction::TRANSACTIONTYPE)
    ->setNewValue(1);

    $xactions = $editor->applyTransactions($conpherence, $xactions);

    return array(
      'phid' => $phid,
      'isDeleted' => 1,
      'message' => 'Successfully deleted room chat',
    );
  }
}
