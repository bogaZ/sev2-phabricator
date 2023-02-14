<?php

final class ManiphestTaskOwnerQATransaction
  extends ManiphestTaskTransactionType {

  const TRANSACTIONTYPE = 'reassign:QA';

  public function generateOldValue($object) {
    return nonempty($object->getOwnerQAPHID(), null);
  }

  public function applyInternalEffects($object, $value) {
    // Update the "ownerQAOrdering" column to contain the full name of the
    // owner, if the task is assigned.

    $handle = null;
    if ($value) {
      $handle = id(new PhabricatorHandleQuery())
        ->setViewer($this->getActor())
        ->withPHIDs(array($value))
        ->executeOne();
    }

    $object->setOwnerQAPHID($value);
  }

  public function getActionStrength() {
    return 120;
  }

  public function getActionName() {
    $old = $this->getOldValue();
    $new = $this->getNewValue();

    if ($this->getAuthorPHID() == $new) {
      return pht('Claimed');
    } else if (!$new) {
      return pht('Unassigned');
    } else if (!$old) {
      return pht('Assigned');
    } else {
      return pht('Reassigned');
    }
  }

  public function getTitle() {
    $old = $this->getOldValue();
    $new = $this->getNewValue();

    if ($this->getAuthorPHID() == $new) {
      return pht(
        'QA %s claimed this task.',
        $this->renderAuthor());
    } else if (!$new) {
      return pht(
        '%s removed QA %s as the assignee of this task.',
        $this->renderAuthor(),
        $this->renderHandle($old));
    } else if (!$old) {
      return pht(
        '%s assigned this task to QA %s.',
        $this->renderAuthor(),
        $this->renderHandle($new));
    } else {
      return pht(
        '%s reassigned this task from QA %s to QA %s.',
        $this->renderAuthor(),
        $this->renderHandle($old),
        $this->renderHandle($new));
    }
  }

  public function getTitleForFeed() {
    $old = $this->getOldValue();
    $new = $this->getNewValue();

    if ($this->getAuthorPHID() == $new) {
      return pht(
        '%s QA claimed %s.',
        $this->renderAuthor(),
        $this->renderObject());
    } else if (!$new) {
      return pht(
        '%s placed %s up for grabs.',
        $this->renderAuthor(),
        $this->renderObject());
    } else if (!$old) {
      return pht(
        '%s assigned %s to QA %s.',
        $this->renderAuthor(),
        $this->renderObject(),
        $this->renderHandle($new));
    } else {
      return pht(
        '%s reassigned %s from QA %s to QA %s.',
        $this->renderAuthor(),
        $this->renderObject(),
        $this->renderHandle($old),
        $this->renderHandle($new));
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

      $assignee_list = id(new PhabricatorPeopleQuery())
        ->setViewer($this->getActor())
        ->withPHIDs(array($new))
        ->execute();

      if (!$assignee_list) {
        $errors[] = $this->newInvalidError(
          pht('User "%s" is not a valid user.', $new));
      }
    }
    return $errors;
  }

  public function getIcon() {
    return 'fa-user';
  }

  public function getColor() {
    $old = $this->getOldValue();
    $new = $this->getNewValue();

    if ($this->getAuthorPHID() == $new) {
      return 'green';
    } else if (!$new) {
      return 'black';
    } else if (!$old) {
      return 'green';
    } else {
      return 'green';
    }

  }

  public function getTransactionTypeForConduit($xaction) {
    return 'owner';
  }

  public function getFieldValuesForConduit($xaction, $data) {
    return array(
      'old' => $xaction->getOldValue(),
      'new' => $xaction->getNewValue(),
    );
  }

}
