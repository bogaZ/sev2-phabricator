<?php

final class ManiphestTaskPointsQATransaction
  extends ManiphestTaskTransactionType {

  const TRANSACTIONTYPE = 'points:QA';

  public function generateOldValue($object) {
    return $this->getValueForPoints($object->getPointsQA());
  }

  public function generateNewValue($object, $value) {
    return $this->getValueForPoints($value);
  }

  public function applyInternalEffects($object, $value) {
    $object->setPointsQA($value);
  }

  public function shouldHide() {
    if (!ManiphestTaskPoints::getIsEnabled()) {
      return true;
    }
    return false;
  }

  public function getTitle() {
    $old = $this->getOldValue();
    $new = $this->getNewValue();

    if ($old === null) {
      return pht(
        '%s set the point value for this QA task to %s.',
        $this->renderAuthor(),
        $this->renderNewValue());
    } else if ($new === null) {
      return pht(
        '%s removed the point value for this QA task.',
        $this->renderAuthor());
    } else {
      return pht(
        '%s changed the point value for this QA task from %s to %s.',
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
        '%s set the QA point value for %s to %s.',
        $this->renderAuthor(),
        $this->renderObject(),
        $this->renderNewValue());
    } else if ($new === null) {
      return pht(
        '%s removed the QA point value for %s.',
        $this->renderAuthor(),
        $this->renderObject());
    } else {
      return pht(
        '%s changed the QA point value for %s from %s to %s.',
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
          pht('QA Points value must be numeric or empty.'));
        continue;
      }
    }

    return $errors;
  }

  public function getIcon() {
    return 'fa-calculator';
  }

  private function getValueForPoints($value) {
    if (!strlen($value)) {
      $value = null;
    }
    if ($value !== null) {
      $value = (double)$value;
    }
    return $value;
  }

  public function getTransactionTypeForConduit($xaction) {
    return 'points';
  }

  public function getFieldValuesForConduit($xaction, $data) {
    return array(
      'old' => $xaction->getOldValue(),
      'new' => $xaction->getNewValue(),
    );
  }


}
