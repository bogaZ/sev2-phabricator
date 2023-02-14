<?php

final class PhabricatorCoursepathReplyHandler
  extends PhabricatorApplicationTransactionReplyHandler {

  public function validateMailReceiver($mail_receiver) {
    if (!($mail_receiver instanceof CoursepathItem)) {
      throw new Exception(pht('Mail receiver is not a %s!', 'Course Paths'));
    }
  }

  public function getObjectPrefix() {
    return 'CRSI';
  }

}
