<?php

final class PhabricatorUserSuiteOnlineTransaction
  extends PhabricatorUserTransactionType {

  const TRANSACTIONTYPE = 'user.suite-online';

  public function generateOldValue($object) {
    return (bool)$object->getIsSuiteOnline();
  }

  public function generateNewValue($object, $value) {
    return (bool)$value;
  }

  public function applyInternalEffects($object, $value) {
    $object->setIsSuiteOnline((int)$value);
  }

  public function getTitle() {
    $new = $this->getNewValue();
    if ($new) {
      return pht(
        '%s has become online.',
        $this->renderAuthor());
    } else {
      return pht(
        '%s has become offline.',
        $this->renderAuthor());
    }
  }

  public function shouldHideForFeed() {
    // Don't publish feed stories about disabling users, since this can be
    // a sensitive action.
    return true;
  }

  public function getRequiredCapabilities(
    $object,
    PhabricatorApplicationTransaction $xaction) {

    // You do not need to be able to edit users to disable them. Instead, this
    // requirement is replaced with a requirement that you have the "Can
    // Disable Users" permission.

    return null;
  }
}
