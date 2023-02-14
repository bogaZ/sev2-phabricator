<?php

final class ConpherenceDeleteTransactionConduitAPIMethod
  extends ConpherenceConduitAPIMethod {

  public function getAPIMethodName() {
    return 'conpherence.deletetransaction';
  }

  public function getMethodDescription() {
    return pht('Delete a transaction message in conpherence room.');
  }

  protected function defineParamTypes() {
    return array(
      'transactionPHIDs' => 'required list<phid>',
    );
  }

  protected function defineReturnType() {
    return 'dict | null';
  }

  protected function defineErrorTypes() {
    return array(
      'ERR_NOT_FOUND'  => pht('Bad message transactionPHID.'),
      'ERR_WRONG_USER' => pht('You are not the creator of this message.'),
      'ERR_NEED_PARAM' => pht('Must pass an transactionPHID.'),
    );
  }

  protected function execute(ConduitAPIRequest $request) {
    $user = $request->getUser()->getPHID();
    $phids = $request->getValue('transactionPHIDs');

    $messages = id(new ConpherenceTransactionComment())->loadAllWhere(
      'transactionPHID IN (%Ls) AND authorPHID = %s',
      array_values($phids),
      $user);

    $map = array();
    $data_phid = array();
    $phid_not_found = array();

    foreach ($messages as $data) {
      $data_phid[] = $data->getTransactionPHID();

      if ($data->getIsDeleted() == 1) {
        $map[$data->getTransactionPHID()] = $data->getIsDeleted();
      }
    }

    $phid_not_found = array_diff($phids, $data_phid);

    if ($map) {
      return array(
        'transactionPHID' => $map,
        'message' => 'Failed to delete, this transaction phid already deleted.',
      );
    }

    if ($phid_not_found) {
      return array(
        'transactionPHID' => $phid_not_found,
        'message' => 'Failed to delete, this transaction phid not found.',
      );
    }

    foreach ($messages as $message) {
      $message->setIsDeleted(1);
      $message->save();
    }

    return array(
      'transactionPHIDs' => $phids,
      'isDeleted' => 1,
      'message' => 'Successfully deleted transaction comments.',
    );
  }
}
