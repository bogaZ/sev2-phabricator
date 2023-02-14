<?php

final class CoursepathItemTestRemoveTransaction
  extends CoursepathItemTestTransactionType {

  const TRANSACTIONTYPE = 'item.test.remove';

  public function generateOldValue($object) {
    return null;
  }

  public function applyExternalEffects($object, $value) {

    $item_tests = id(new CoursepathItemTestQuery())
      ->setViewer($this->getActor())
      ->withIDs($value)
      ->needOptions(true)
      ->execute();
    $item_tests = mpull($item_tests, null, 'getID');

    foreach ($value as $phid) {
      foreach ($item_tests[$phid]->getOptions() as $option) {
        $option->delete();
      }
      $item_tests[$phid]->delete();
    }

    return;
  }

  public function getTitle() {
    $new = $this->getNewValue();
    if (!is_array($new)) {
      $new = array();
    }
    $handles = $this->renderHandleList($new);
    return pht(
      '%s removed %s skill test(s): %s.',
      $this->renderAuthor(),
      new PhutilNumber(count($new)),
      $handles);
  }

  public function getTitleForFeed() {
    $new = $this->getNewValue();
    if (!is_array($new)) {
      $new = array();
    }
    return pht(
      '%s removed %s from skill test.',
      $this->renderAuthor(),
      $this->renderObject());
  }

  public function getIcon() {
    return 'fa-user-times';
  }

  public function validateTransactions($object, array $xactions) {
    $errors = array();

    foreach ($xactions as $xaction) {
      $test_ids = $xaction->getNewValue();
      if (!$test_ids) {
        $errors[] = $this->newRequiredError(
          pht('Test is required.'));
        continue;
      }

      foreach ($test_ids as $test_id) {
        $test = id(new CoursepathItemTestQuery())
          ->setViewer($this->getActor())
          ->withIDs(array($test_id))
          ->executeOne();
        if (!$test) {
          $errors[] = $this->newInvalidError(
            pht(
              'Test with id "%s" has not been created.',
              $test_id));
        }
      }
    }

    return $errors;
  }
}
