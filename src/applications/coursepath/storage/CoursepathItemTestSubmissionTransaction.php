<?php

final class CoursepathItemTestSubmissionTransaction
  extends PhabricatorModularTransaction {

  const MAILTAG_DETAILS = 'coursepath:submission:details';
  const MAILTAG_COMMENT = 'coursepath:submission:comment';
  const MAILTAG_OTHER  = 'coursepath:submission:other';

  public function getApplicationName() {
    return 'coursepath';
  }

  public function getApplicationTransactionType() {
    return CoursepathItemTestSubmissionPHIDType::TYPECONST;
  }

  public function getApplicationTransactionCommentObject() {
    return new CoursepathTransactionComment();
  }

  public function getBaseTransactionClass() {
    return 'CoursepathItemTestSubmissionTransactionType';
  }

  public function getMailTags() {
    $tags = parent::getMailTags();

    switch ($this->getTransactionType()) {
      case PhabricatorTransactions::TYPE_COMMENT:
        $tags[] = self::MAILTAG_COMMENT;
        break;
      default:
        $tags[] = self::MAILTAG_OTHER;
        break;
    }
    return $tags;
  }

}
