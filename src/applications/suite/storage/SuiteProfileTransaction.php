<?php

final class SuiteProfileTransaction
  extends PhabricatorModularTransaction {

  const MAILTAG_CHANGE = 'suite:profile-change';
  const MAILTAG_OTHER  = 'suite:other';

  public function getApplicationName() {
    return 'suite';
  }

  public function getApplicationTransactionType() {
    return SuiteProfilePHIDType::TYPECONST;
  }

  public function getApplicationTransactionCommentObject() {
    return null;
  }

  public function getBaseTransactionClass() {
    return 'SuiteProfileTransactionType';
  }

  public function getMailTags() {
    $tags = parent::getMailTags();

    switch ($this->getTransactionType()) {
      case SuiteProfileIsRspTransaction::TRANSACTIONTYPE:
      case SuiteProfileUpForTransaction::TRANSACTIONTYPE:
      case SuiteProfileGraduationTargetMonthTransaction::TRANSACTIONTYPE:
        $tags[] = self::MAILTAG_CHANGE;
        break;
      default:
        $tags[] = self::MAILTAG_OTHER;
        break;
    }
    return $tags;
  }

}
