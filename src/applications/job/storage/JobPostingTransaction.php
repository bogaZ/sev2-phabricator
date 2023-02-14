<?php

final class JobPostingTransaction
  extends PhabricatorModularTransaction {

  const MAILTAG_DETAILS = 'job:details';
  const MAILTAG_COMMENT = 'job:comment';
  const MAILTAG_OTHER  = 'job:other';

  public function getApplicationName() {
    return 'job';
  }

  public function getApplicationTransactionType() {
    return JobPostingPHIDType::TYPECONST;
  }

  public function getApplicationTransactionCommentObject() {
    return new JobPostingTransactionComment();
  }

  public function getBaseTransactionClass() {
    return 'JobPostingTransactionType';
  }

  public function getMailTags() {
    $tags = parent::getMailTags();

    switch ($this->getTransactionType()) {
      case PhabricatorTransactions::TYPE_COMMENT:
        $tags[] = self::MAILTAG_COMMENT;
        break;
      case JobPostingNameTransaction::TRANSACTIONTYPE:
        $tags[] = self::MAILTAG_DETAILS;
        break;
      default:
        $tags[] = self::MAILTAG_OTHER;
        break;
    }
    return $tags;
  }

}
