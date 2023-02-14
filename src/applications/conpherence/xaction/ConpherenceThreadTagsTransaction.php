<?php

final class ConpherenceThreadTagsTransaction
  extends ConpherenceThreadTransactionType {

  const TRANSACTIONTYPE = 'tags.conpherence';

  public function generateOldValue($object) {
    return nonempty($object->getTagsPHID(), null);
  }

  public function applyInternalEffects($object, $value) {
    // Update the "ownerOrdering" column to contain the full name of the
    // owner, if the task is assigned.

    $object->setTagsPHID($value);
  }

  public function getActionStrength() {
    return 120;
  }


  public function getTitle() {
    $old = $this->getOldValue();
    $new = $this->getNewValue();

    if (strlen($new)) {
      return pht(
        '%s set the room tags to %s.',
        $this->renderAuthor(),
        $this->renderNewValue());
    } else {
      return pht(
        '%s removed the room tags.',
        $this->renderAuthor());
    }
  }

  public function getTitleForFeed() {
    $old = $this->getOldValue();
    $new = $this->getNewValue();

    if (strlen($new)) {
      return pht(
        '%s set the room tags to %s in %s.',
        $this->renderAuthor(),
        $this->renderNewValue(),
        $this->renderObject());
    } else {
      return pht(
        '%s removed the room tags for %s.',
        $this->renderAuthor(),
        $this->renderObject());
    }
  }

  public function validateTransactions($object, array $xactions) {
    $errors = array();
    foreach ($xactions as $xaction) {
      $old = $xaction->getOldValue();
      $new = $xaction->getNewValue();
      if (!strlen($new)) {
        continue;
      }

      if ($new === $old) {
        continue;
      }

      $assignee_list = id(new PhabricatorProjectQuery())
        ->setViewer($this->getActor())
        ->withPHIDs(array($new))
        ->execute();

      if (!$assignee_list) {
        $errors[] = $this->newInvalidError(
          pht('"%s" is not a valid tags.', $new));
      }
    }
    return $errors;
  }

  public function getTransactionTypeForConduit($xaction) {
    return 'tags.conpherence';
  }

  public function getFieldValuesForConduit($xaction, $data) {
    return array(
      'old' => $xaction->getOldValue(),
      'new' => $xaction->getNewValue(),
    );
  }

}
