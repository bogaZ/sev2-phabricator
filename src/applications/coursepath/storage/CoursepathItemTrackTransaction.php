<?php

final class CoursepathItemTrackTransaction
  extends PhabricatorModularTransaction {

  const MAILTAG_DETAILS = 'coursepath:track:details';
  const MAILTAG_COMMENT = 'coursepath:track:comment';
  const MAILTAG_OTHER  = 'coursepath:track:other';

  public function getApplicationName() {
    return 'coursepath';
  }

  public function getApplicationTransactionType() {
    return CoursepathItemTrackPHIDType::TYPECONST;
  }

  public function getApplicationTransactionCommentObject() {
    return new CoursepathTransactionComment();
  }

  public function getBaseTransactionClass() {
    return 'CoursepathItemTrackTransactionType';
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
