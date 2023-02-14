<?php

final class PhabricatorJobReplyHandler
  extends PhabricatorApplicationTransactionReplyHandler {

  public function validateMailReceiver($mail_receiver) {
    if (!($mail_receiver instanceof JobPosting)) {
      throw new Exception(pht('Mail receiver is not a %s!', 'Job'));
    }
  }

  public function getObjectPrefix() {
    return 'CRSI';
  }

}
