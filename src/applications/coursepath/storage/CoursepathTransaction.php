<?php

final class CoursepathTransaction
  extends PhabricatorModularTransaction {

  const MAILTAG_DETAILS = 'coursepath:details';
  const MAILTAG_COMMENT = 'coursepath:comment';
  const MAILTAG_OTHER  = 'coursepath:other';

  public function getApplicationName() {
    return 'coursepath';
  }

  public function getApplicationTransactionType() {
    return CoursepathItemPHIDType::TYPECONST;
  }

  public function getApplicationTransactionCommentObject() {
    return new CoursepathTransactionComment();
  }

  public function getBaseTransactionClass() {
    return 'CoursepathItemTransactionType';
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
