<?php

final class LobbyStickitNoteTypeTransaction
  extends LobbyStickitTransactionType {

  const TRANSACTIONTYPE = 'lobby:stickit-notetype';

  public function generateOldValue($object) {
    return $object->getNoteType();
  }

  public function applyInternalEffects($object, $value) {
    $object->setNoteType($value);
  }

  public function getTitle() {
    return pht(
      '%s changed type from %s to %s.',
      $this->renderAuthor(),
      $this->renderOldValue(),
      $this->renderNewValue());
  }

  public function validateTransactions($object, array $xactions) {
    $errors = array();

    if ($this->isEmptyTextTransaction($object->getNoteType(), $xactions)) {
      $errors[] = $this->newRequiredError(
        pht('Stickit must have a type.'));
    }

    $max_length = (int)$object->getColumnMaximumByteLength('noteType');
    foreach ($xactions as $xaction) {
      $new_value = $xaction->getNewValue();
      $new_length = strlen($new_value);
      if ($new_length > $max_length) {
        $errors[] = $this->newInvalidError(
          pht('The note type value can be no longer than %s characters.',
          new PhutilNumber($max_length)));
      }
    }

    return $errors;
  }

}
