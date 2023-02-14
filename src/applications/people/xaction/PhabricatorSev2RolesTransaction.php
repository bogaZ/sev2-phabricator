<?php

final class PhabricatorSev2RolesTransaction
  extends PhabricatorUserTransactionType {

  const TRANSACTIONTYPE = 'sev2.roles.edit';
   private $ifNull = '[]';
  public function generateOldValue($object) {
    if ($object->getCustomRoles() != null) {
        return $object->getCustomRoles();
    } else {
        return $this->ifNull;
    }
  }

  public function generateNewValue($object, $value) {
    return $value;
  }

  public function applyInternalEffects($object, $value) {

    $object->setCustomRoles($value);
  }

  public function validateTransactions($object, array $xactions) {
    $actor = $this->getActor();
    $errors = array();
    foreach ($xactions as $xaction) {
      $old = json_encode($xaction->getOldValue());
      $new = $xaction->getNewValue();
      if ($old === $new) {
        continue;
      }

      $is_admin = $actor->getIsAdmin();
      $is_omnipotent = $actor->isOmnipotent();

      if (!$is_admin && !$is_omnipotent) {
        $errors[] = $this->newInvalidError(
          pht('You must be an administrator to create %s.', $new),
          $xaction);
      }
    }

    return $errors;
  }

  public function getTitle() {
    $new = $this->getNewValue();
    if ($new) {
      return pht(
        '%s empowered this user as an %s.',
        $this->renderAuthor(), $new);
    } else {
      return pht(
        '%s defrocked this user.',
        $this->renderAuthor());
    }
  }

  public function getTitleForFeed() {
    $new = $this->getNewValue();
    if ($new) {
      return pht(
        '%s empowered %s as an %s.',
        $this->renderAuthor(),
        $this->renderObject(),
        $new);
    } else {
      return pht(
        '%s defrocked %s.',
        $this->renderAuthor(),
        $this->renderObject());
    }
  }

  public function getRequiredCapabilities(
    $object,
    PhabricatorApplicationTransaction $xaction) {

    // Unlike normal user edits, admin promotions require admin
    // permissions, which is enforced by validateTransactions().

    return null;
  }

  public function shouldTryMFA(
    $object,
    PhabricatorApplicationTransaction $xaction) {
    return true;
  }

}
