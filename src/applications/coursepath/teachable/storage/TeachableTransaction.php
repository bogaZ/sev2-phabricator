<?php

final class TeachableTransaction
  extends PhabricatorModularTransaction {

  const MAILTAG_DETAILS = 'teachable:details';
  const MAILTAG_COMMENT = 'teachable:comment';
  const MAILTAG_OTHER  = 'teachable:other';

  public function getApplicationName() {
    return 'coursepath';
  }

  public function getApplicationTransactionType() {
    return TeachableConfigurationPHIDType::TYPECONST;
  }

  public function getApplicationTransactionCommentObject() {
    return new CoursepathTransactionComment();
  }

  public function getBaseTransactionClass() {
    return 'TeachableConfigurationTransactionType';
  }

  public function getMailTags() {
    $tags = parent::getMailTags();

    switch ($this->getTransactionType()) {
      case PhabricatorTransactions::TYPE_COMMENT:
        $tags[] = self::MAILTAG_COMMENT;
        break;
      case CoursepathItemNameTransaction::TRANSACTIONTYPE:
        $tags[] = self::MAILTAG_DETAILS;
        break;
      default:
        $tags[] = self::MAILTAG_OTHER;
        break;
    }
    return $tags;
  }

}
