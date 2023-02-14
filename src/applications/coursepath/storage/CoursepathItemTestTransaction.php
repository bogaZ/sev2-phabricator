<?php

final class CoursepathItemTestTransaction
  extends PhabricatorModularTransaction {

  const MAILTAG_DETAILS = 'coursepath:test:details';
  const MAILTAG_COMMENT = 'coursepath:test:comment';
  const MAILTAG_OTHER  = 'coursepath:test:other';

  public function getApplicationName() {
    return 'coursepath';
  }

  public function getApplicationTransactionType() {
    return CoursepathItemTestPHIDType::TYPECONST;
  }

  public function getApplicationTransactionCommentObject() {
    return new CoursepathTransactionComment();
  }

  public function getBaseTransactionClass() {
    return 'CoursepathItemTestTransactionType';
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
