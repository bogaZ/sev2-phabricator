<?php

final class CoursepathItemTrackRemoveTransaction
  extends CoursepathItemTrackTransactionType {

  const TRANSACTIONTYPE = 'item.track.remove';

  public function generateOldValue($object) {
    return null;
  }

  public function applyExternalEffects($object, $value) {

    $tracks = id(new CoursepathItemTrackQuery())
      ->setViewer($this->getActor())
      ->withIDs($value)
      ->execute();
    $tracks = mpull($tracks, null, 'getID');

    foreach ($value as $phid) {
      $tracks[$phid]->delete();
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
      '%s removed %s teachable course(s): %s.',
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
      '%s removed %s from coursepath.',
      $this->renderAuthor(),
      $this->renderObject());
  }

  public function getIcon() {
    return 'fa-user-times';
  }

  public function validateTransactions($object, array $xactions) {
    $errors = array();

    foreach ($xactions as $xaction) {
      $track_ids = $xaction->getNewValue();
      if (!$track_ids) {
        $errors[] = $this->newRequiredError(
          pht('Test is required.'));
        continue;
      }

      foreach ($track_ids as $track_id) {
        $track = id(new CoursepathItemTrackQuery())
          ->setViewer($this->getActor())
          ->withIDs(array($track_id))
          ->executeOne();
        if (!$track) {
          $errors[] = $this->newInvalidError(
            pht(
              'Track with id "%s" has not been created.',
              $track_id));
        }
      }
    }

    return $errors;
  }
}
