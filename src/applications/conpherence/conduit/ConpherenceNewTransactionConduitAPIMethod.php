<?php

final class ConpherenceNewTransactionConduitAPIMethod
  extends ConpherenceConduitAPIMethod {

  public function getAPIMethodName() {
    return 'conpherence.newtransaction';
  }

  public function getMethodDescription() {
    return pht(
      'Create a new transactions for the logged in user within a specific '.
      'Conpherence room. You can specify the room by ID or PHID. '.
      'Content will be parsed with markdown engine');
  }

  protected function defineParamTypes() {
    return array(
      'roomID' => 'optional int',
      'roomPHID' => 'optional phid',
      'text' => 'required string',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function defineErrorTypes() {
    return array(
      'ERR_NO_PUBLIC' => pht('You only able to post on public channel'),
      'ERR_NO_TEXT' => pht('You must specify text to send'),
      'ERR_USAGE_NO_UPDATES' => pht(
        'You must specify data that actually updates the Conpherence.'),
      'ERR_USAGE_NO_ROOM_ID' => pht(
        'You must specify a room id or room PHID to query transactions '.
        'from.'),
    );
  }

  protected function execute(ConduitAPIRequest $request) {
    $user = $request->getUser();
    $room_id = $request->getValue('roomID');
    $room_phid = $request->getValue('roomPHID');

    $query = id(new ConpherenceThreadQuery())
      ->setViewer($user);

    if ($room_id) {
      $query->withIDs(array($room_id));
    } else if ($room_phid) {
      $query->withPHIDs(array($room_phid));
    } else {
      throw new ConduitException('ERR_USAGE_NO_ROOM_ID');
    }

    $conpherence = $query->executeOne();

    if (!$conpherence->getIsPublic() && !$user->canEstablishAPISessions()) {
      throw new ConduitException('ERR_NO_PUBLIC');
    }

    $text = $request->getValue('text');
    if (!$text) {
      throw new ConduitException('ERR_NO_TEXT');
    }

    $source = $request->newContentSource();
    $editor = id(new ConpherenceEditor())
        ->setContinueOnNoEffect(true)
        ->setContentSource($source)
        ->setActor($user);

    $xactions = $editor->generateTransactionsFromText(
              $user,
              $conpherence,
              $text);

    try {
      $xactions = $editor->applyTransactions($conpherence, $xactions);
    } catch (PhabricatorApplicationTransactionNoEffectException $ex) {
      throw new ConduitException('ERR_USAGE_NO_UPDATES');
    }

    return true;
  }

}
