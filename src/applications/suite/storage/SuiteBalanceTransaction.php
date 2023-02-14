<?php

final class SuiteBalanceTransaction
  extends PhabricatorModularTransaction
  implements PhabricatorMarkupInterface {

  protected $creditAmount;
  protected $debitAmount;
  protected $isWithdrawable = false;
  protected $cartPHID;
  protected $remarks;

  private $cart = self::ATTACHABLE;

  const MAILTAG_CHANGE = 'suite:balance-change';
  const MAILTAG_COMMENT = 'suite:comment';
  const MAILTAG_OTHER  = 'suite:other';

  public function getApplicationName() {
    return 'suite';
  }

  public function getRenderingTarget() {
    return PhabricatorApplicationTransaction::TARGET_TEXT;
  }

  public function getApplicationTransactionType() {
    return SuiteBalancePHIDType::TYPECONST;
  }

  public function getApplicationTransactionCommentObject() {
    return new SuiteBalanceTransactionComment();
  }

  public function getBaseTransactionClass() {
    return 'SuiteBalanceTransactionType';
  }

  protected function getConfiguration() {
    $config = parent::getConfiguration();

    $config[self::CONFIG_COLUMN_SCHEMA] = array(
      'cartPHID' => 'phid?',
      'creditAmount' => 'uint32?',
      'debitAmount' => 'uint32?',
      'isWithdrawable' => 'bool',
      'remarks' => 'text?',
    ) + $config[self::CONFIG_COLUMN_SCHEMA];

    return $config;
  }

  public function getMailTags() {
    $tags = parent::getMailTags();

    switch ($this->getTransactionType()) {
      case PhabricatorTransactions::TYPE_COMMENT:
        $tags[] = self::MAILTAG_COMMENT;
        break;
      case SuiteBalanceWithdrawableAmountTransaction::TRANSACTIONTYPE:
      case SuiteBalanceAmountTransaction::TRANSACTIONTYPE:
        $tags[] = self::MAILTAG_CHANGE;
        break;
      default:
        $tags[] = self::MAILTAG_OTHER;
        break;
    }
    return $tags;
  }


/* -(  PhabricatorMarkupInterface  )----------------------------------------- */


  public function getMarkupFieldKey($field) {
    return PhabricatorPHIDConstants::PHID_TYPE_XCMT.':'.$this->getPHID();
  }


  public function newMarkupEngine($field) {
    return PhabricatorMarkupEngine::getEngine();
  }


  public function getMarkupText($field) {
    return $this->getRemarks();
  }


  public function didMarkupText($field, $output, PhutilMarkupEngine $engine) {
    require_celerity_resource('phabricator-remarkup-css');
    return phutil_tag(
      'div',
      array(
        'class' => 'phabricator-remarkup',
      ),
      $output);
  }


  public function shouldUseMarkupCache($field) {
    return (bool)$this->getPHID();
  }


}
