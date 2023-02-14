<?php

final class ManiphestTaskProgressTransaction
  extends ManiphestTaskTransactionType {

  const TRANSACTIONTYPE = 'progress';

  public function generateOldValue($object) {
    return $this->getValueForProgresses($object->getProgress());
  }

  public function generateNewValue($object, $value) {
    return $this->getValueForProgresses($value);
  }

  public function applyInternalEffects($object, $value) {
    $object->setProgress($value);
  }


  public function getTitle() {
    $old = $this->getOldValue();
    $new = $this->getNewValue();

    if ($old === null) {
      return pht(
        '%s set the progress value for this task to %s.',
        $this->renderAuthor(),
        $this->renderNewValue());
    } else if ($new === null) {
      return pht(
        '%s removed the progress value for this task.',
        $this->renderAuthor());
    } else {
      return pht(
        '%s changed the progress value for this task from %s to %s.',
        $this->renderAuthor(),
        $this->renderOldValue(),
        $this->renderNewValue());
    }
  }

  public function getTitleForFeed() {
    $old = $this->getOldValue();
    $new = $this->getNewValue();

    if ($old === null) {
      return pht(
        '%s set the progress value for %s to %s.',
        $this->renderAuthor(),
        $this->renderObject(),
        $this->renderNewValue());
    } else if ($new === null) {
      return pht(
        '%s removed the progress value for %s.',
        $this->renderAuthor(),
        $this->renderObject());
    } else {
      return pht(
        '%s changed the progress value for %s from %s to %s.',
        $this->renderAuthor(),
        $this->renderObject(),
        $this->renderOldValue(),
        $this->renderNewValue());
    }
  }


  public function validateTransactions($object, array $xactions) {
    $errors = array();

    foreach ($xactions as $xaction) {
      $new = $xaction->getNewValue();
      if (strlen($new) && !is_numeric($new)) {
        $errors[] = $this->newInvalidError(
          pht('Progresses value must be numeric or empty.'));
        continue;
      }

      if ((double)$new < 0) {
        $errors[] = $this->newInvalidError(
          pht('Progresses value must be nonnegative.'));
        continue;
      }
    }

    return $errors;
  }

  public function getIcon() {
    return 'fa-calculator';
  }

  private function getValueForProgresses($value) {
    if (!strlen($value)) {
      $value = null;
    }
    if ($value !== null) {
      $value = (double)$value;
    }
    return $value;
  }

  public function getTransactionTypeForConduit($xaction) {
    return 'progresses';
  }

  public function getFieldValuesForConduit($xaction, $data) {
    return array(
      'old' => $xaction->getOldValue(),
      'new' => $xaction->getNewValue(),
    );
  }


}
