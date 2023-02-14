<?php

final class SuiteProfileReplyHandler
  extends PhabricatorApplicationTransactionReplyHandler {

  public function validateMailReceiver($mail_receiver) {
    if (!($mail_receiver instanceof SuiteProfile)) {
      throw new Exception(pht('Mail receiver is not a %s!', 'Suite Profile'));
    }
  }

  public function getObjectPrefix() {
    return 'SUTP';
  }

}
