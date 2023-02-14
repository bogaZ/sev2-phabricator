<?php

final class LobbyStateCurrentChannelTransaction
  extends LobbyStateTransactionType {

  const TRANSACTIONTYPE = 'lobby:state-currentchannel';


  public function generateOldValue($object) {
    return $object->getCurrentChannel();
  }

  public function generateNewValue($object, $value) {
    return $value;
  }

  public function applyInternalEffects($object, $value) {
    $object->setCurrentChannel($value);
  }

  public function getTitle() {
      return pht(
        '%s joining a channel.',
        $this->renderAuthor());
  }

}
