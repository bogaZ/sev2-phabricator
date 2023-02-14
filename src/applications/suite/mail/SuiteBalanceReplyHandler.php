<?php

final class SuiteBalanceReplyHandler
  extends PhabricatorApplicationTransactionReplyHandler {

  public function validateMailReceiver($mail_receiver) {
    if (!($mail_receiver instanceof SuiteBalance)) {
      throw new Exception(pht('Mail receiver is not a %s!', 'Suite Balance'));
    }
  }

  public function getObjectPrefix() {
    return 'SUTB';
  }

}
