<?php

final class SuiteBalance
  extends SuiteDAO
  implements PhabricatorPolicyInterface,
    PhabricatorApplicationTransactionInterface,
    PhabricatorSubscribableInterface,
    PhabricatorMentionableInterface,
    PhabricatorConduitResultInterface {


  protected $ownerPHID;
  protected $accountPHID;
  protected $mailKey;
  protected $viewPolicy;
  protected $editPolicy;
  protected $amount = 0;
  protected $withdrawableAmount = 0;

  private $owner = null;
  private $account = null;

  private $subscriberPHIDs = self::ATTACHABLE;

  const ACCEPTED_CURRENCY = 'IDR';

  public static function createNewBalance(PhabricatorUser $actor,
      PhortuneAccount $account,
      PhabricatorContentSource $content_source) {
      $new_balance = self::initializeNewBalance($actor, $account);

      $xactions = array();
      $xactions[] = id(new SuiteBalanceTransaction())
        ->setRemarks('Balance opened')
        ->setCreditAmount(1000)
        ->setIsWithdrawable(0)
        ->setTransactionType(SuiteBalanceAmountTransaction::TRANSACTIONTYPE)
        ->setNewValue(1000);

      $editor = id(new SuiteBalanceEditor())
        ->setActor($actor)
        ->setContentSource($content_source)
        ->setContinueOnNoEffect(true);

      // We create an profile for you the first time you visit Suite.
      $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();

        $editor->applyTransactions($new_balance, $xactions);

        $new_balance->save();

      unset($unguarded);

      return $new_balance;
  }

  public static function initializeNewBalance(PhabricatorUser $actor,
                                          PhortuneAccount $account) {

    $app = id(new PhabricatorApplicationQuery())
      ->setViewer($actor)
      ->withClasses(array('PhabricatorSuiteApplication'))
      ->executeOne();

    $view_policy = $app->getPolicy(
      PhabricatorSuiteCapabilityManageBilling::CAPABILITY);
    $edit_policy = $app->getPolicy(
      PhabricatorSuiteCapabilityManageBilling::CAPABILITY);

    return id(new self())
      ->setOwnerPHID($actor->getPHID())
      ->setAccountPHID($account->getPHID())
      ->setViewPolicy($view_policy)
      ->setEditPolicy($edit_policy)
      ->setAmount(0)
      ->setWithdrawableAmount(0);
  }

  protected function getConfiguration() {
    return array(
      self::CONFIG_AUX_PHID => true,
      self::CONFIG_COLUMN_SCHEMA => array(
        'mailKey' => 'bytes20',
        'amount' => 'uint32?',
        'withdrawableAmount' => 'uint32?',
      ),
      self::CONFIG_KEY_SCHEMA => array(
        'key_account' => array(
          'columns' => array('accountPHID', 'ownerPHID'),
        ),
      ),
    ) + parent::getConfiguration();
  }

  /**
   * Primary addition method
   */
  public function add(PhabricatorUser $actor,
    PhabricatorContentSource $content_source,
    $amount, $is_withdrawable,
    $remarks, $cart_phid = null) {
      switch ($is_withdrawable) {
        case true:
          $xtype = SuiteBalanceWithdrawableAmountTransaction::TRANSACTIONTYPE;
          $valid = (($amount + $this->getWithdrawableAmount()) > 0);
          break;

        default:
          $xtype = SuiteBalanceAmountTransaction::TRANSACTIONTYPE;
          $valid = (($amount + $this->getAmount()) > 0);
          break;
      }

      if (!$valid) {
        throw new SuiteInvalidOperationException(
          'Invalid addition amount.');
      }


      $addition = id(new SuiteBalanceTransaction())
        ->setRemarks($remarks)
        ->setCreditAmount($amount)
        ->setIsWithdrawable((int)$is_withdrawable)
        ->setTransactionType($xtype)
        ->setNewValue($amount);

      $xactions = array($addition);

      $editor = id(new SuiteBalanceEditor())
        ->setActor($actor)
        ->setContentSource($content_source)
        ->setContinueOnNoEffect(true);

      // We create an profile for you the first time you visit Suite.
      $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();

        $editor->applyTransactions($this, $xactions);

        $this->save();

      unset($unguarded);

      return $this;
  }

  /**
   * Primary substraction method
   */
   public function sub(PhabricatorUser $actor,
     PhabricatorContentSource $content_source,
     $amount, $is_withdrawable,
     $remarks, $cart_phid = null) {
      switch ($is_withdrawable) {
        case true:
          $xtype = SuiteBalanceWithdrawableAmountTransaction::TRANSACTIONTYPE;
          $valid = $amount <= $this->getWithdrawableAmount();
          break;

        default:
          $valid = $amount <= $this->getAmount();
          $xtype = SuiteBalanceAmountTransaction::TRANSACTIONTYPE;
          break;
      }

      if (!$valid) {
        throw new SuiteInvalidOperationException(
          'Lack of balance');
      }

      $substraction = id(new SuiteBalanceTransaction())
        ->setRemarks($remarks)
        ->setDebitAmount($amount)
        ->setIsWithdrawable((int)$is_withdrawable)
        ->setTransactionType($xtype)
        ->setNewValue(-$amount);

      if ($cart_phid != null) {
        $substraction->setCartPHID($cart_phid);
      }

      $xactions = array($substraction);

      $editor = id(new SuiteBalanceEditor())
        ->setActor($actor)
        ->setContentSource($content_source)
        ->setContinueOnNoEffect(true);

      // We create an profile for you the first time you visit Suite.
      $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();

        $editor->applyTransactions($this, $xactions);

        $this->save();

      unset($unguarded);

      return $this;
  }

  public function loadUser() {
    if ($this->owner) {
      return $this->owner;
    }

    $user_dao = new PhabricatorUser();
    $this->owner = $user_dao->loadOneWhere('phid = %s',
      $this->getOwnerPHID());

    return $this->owner;
  }

  public function loadAccount() {
    if ($this->account) {
      return $this->account;
    }

    $account_dao = new PhortuneAccount();
    $this->account = $account_dao->loadOneWhere('phid = %s',
      $this->getAccountPHID());

    return $this->account;
  }

  public function generatePHID() {
    return PhabricatorPHID::generateNewPHID(
      SuiteBalancePHIDType::TYPECONST);
  }

  public function attachSubscriberPHIDs(array $phids) {
    $this->subscriberPHIDs = $phids;
    return $this;
  }


  public function getMonogram() {
    return 'SUTB'.$this->getID();
  }

  public function getViewURI() {
    return '/suite/balance/view/'.$this->getID().'/';
  }

  public function getAmountAsCurrency() {
    return PhortuneCurrency::newFromValueAndCurrency(
      $this->getAmount(), self::ACCEPTED_CURRENCY);
  }

  public function getWithdrawableAmountAsCurrency() {
    return PhortuneCurrency::newFromValueAndCurrency(
      $this->getWithdrawableAmount(), self::ACCEPTED_CURRENCY);
  }

  public function save() {
    if (!$this->getMailKey()) {
      $this->setMailKey(Filesystem::readRandomCharacters(20));
    }

    return parent::save();
  }

  public function getCapabilities() {
    return array(
      PhabricatorPolicyCapability::CAN_VIEW,
      PhabricatorPolicyCapability::CAN_EDIT,
    );
  }

  public function getPolicy($capability) {
    if ($capability == PhabricatorPolicyCapability::CAN_EDIT) {
      return $this->getEditPolicy();
    }

    return $this->getViewPolicy();
  }

  public function hasAutomaticCapability($capability, PhabricatorUser $viewer) {
    return $viewer->getPHID() == $this->ownerPHID;
  }

  public function describeAutomaticCapability($capability) {
    switch ($capability) {
      case PhabricatorPolicyCapability::CAN_VIEW:
        return pht('Owners of balance can always view it.');
      case PhabricatorPolicyCapability::CAN_EDIT:
        return pht('Owners of balance can always edit it.');
    }
    return null;
  }

  public function getApplicationTransactionEditor() {
    return new SuiteBalanceEditor();
  }

  public function getApplicationTransactionTemplate() {
    return new SuiteBalanceTransaction();
  }


/* -(  PhabricatorSubscribableInterface  )----------------------------------- */


  public function isAutomaticallySubscribed($phid) {
    return false;
  }


/* -(  PhabricatorConduitResultInterface  )---------------------------------- */


  public function getFieldSpecificationsForConduit() {
    return array(
      id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('ownerPHID')
        ->setType('string')
        ->setDescription(pht('User PHID of the balance owner.')),
      id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('accountPHID')
        ->setType('phid')
        ->setDescription(pht('Account PHID of the Phortune account.')),
    );
  }

  public function getFieldValuesForConduit() {
    return array(
      'ownerPHID' => $this->getOwnerPHID(),
      'accountPHID' => $this->getAccountPHID(),
      'amount' => $this->getWithdrawableAmount(),
    );
  }

  public function getConduitSearchAttachments() {
    return array();
  }

}
