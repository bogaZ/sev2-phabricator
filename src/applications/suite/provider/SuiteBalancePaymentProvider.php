<?php

final class SuiteBalancePaymentProvider extends PhortunePaymentProvider {

  public static function loadBalanceMethod(
    PhabricatorUser $actor,
    PhortuneAccount $account,
    PhabricatorContentSource $content_source) {

    $existing_method = id(new PhortunePaymentMethodQuery())
      ->setViewer($actor)
      ->withAccountPHIDs(array($account->getPHID()))
      ->withStatuses(
        array(
          PhortunePaymentMethod::STATUS_ACTIVE,
        ))
      ->executeOne();
    if ($existing_method) {
      // Create balance only once
      return $existing_method;
    }

    $refactory = id(new PhortuneMerchantQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->executeOne();

    if (!$refactory) {
      throw new SuiteMissingOmniMerchantException(
        'Refactory merchant is missing!');
    }

    $viewer = $actor;

    $provider_configs = id(new PhortunePaymentProviderConfigQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withMerchantPHIDs(array($refactory->getPHID()))
      ->execute();
    $providers = mpull($provider_configs, 'buildProvider', 'getID');

    foreach ($providers as $key => $provider) {
      if (!$provider->isEnabled()) {
        unset($providers[$key]);
      }
    }

    if (empty($providers)) {
      // Initiate our provider, if there is no active provider
      $balance_provider = PhortunePaymentProviderConfig::initializeNewProvider(
        $refactory);
      $balance_provider->setProviderClass(__CLASS__);
      $balance_provider->setIsEnabled(1);

      $xactions = array();
      $xactions[] = id(new PhortunePaymentProviderConfigTransaction())
        ->setTransactionType(
          PhortunePaymentProviderConfigTransaction::TYPE_CREATE)
        ->setNewValue(true);

      $xactions[] = id(new PhortunePaymentProviderConfigTransaction())
        ->setTransactionType(
          PhortunePaymentProviderConfigTransaction::TYPE_PROPERTY)
        ->setMetadataValue(
          PhortunePaymentProviderConfigTransaction::PROPERTY_KEY,
          'codename')
        ->setNewValue('suiteBalance');

      $editor = id(new PhortunePaymentProviderConfigEditor())
        ->setActor($actor)
        ->setContentSource($content_source);

      // We create an account for you the first time you visit Phortune.
      $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();

        $editor->applyTransactions($balance_provider, $xactions);

      unset($unguarded);

      $providers = array($balance_provider);
    }

    $suite_balance = head($providers);

    $method = id(new PhortunePaymentMethod())
      ->setAccountPHID($account->getPHID())
      ->setAuthorPHID($actor->getPHID())
      ->setMerchantPHID($refactory->getPHID())
      ->setProviderPHID($suite_balance->getProviderConfig()->getPHID())
      ->setStatus(PhortunePaymentMethod::STATUS_ACTIVE);


    $request = new AphrontRequest('', '/');
    $request->setRequestData(array('userPHID' => $actor->getPHID()));
    try {
      $suite_balance->createPaymentMethodFromRequest(
        $request,
        $method,
        array());
    } catch (PhortuneDisplayException $exception) {
      throw $exception;
    } catch (Exception $ex) {
      throw $ex;
    } catch (Throwable $ex) {
      throw $ex;
    }

    $xactions = array();

    $xactions[] = $method->getApplicationTransactionTemplate()
      ->setTransactionType(PhabricatorTransactions::TYPE_CREATE)
      ->setNewValue(true);

    $editor = id(new PhortunePaymentMethodEditor())
      ->setActor($viewer)
      ->setContentSource($content_source)
      ->setContinueOnNoEffect(true)
      ->setContinueOnMissingFields(true);

    $editor->applyTransactions($method, $xactions);

    return $method;
  }

  public function isAcceptingLivePayments() {
    return true;
  }

  public function getName() {
    return pht('Suite Balance');
  }

  public function getConfigureName() {
    return pht('Suite Balance');
  }

  public function getConfigureDescription() {
    return pht(
      'Allows you to accept payments with a Refactory Suite balance.');
  }

  public function getConfigureProvidesDescription() {
    return pht('This merchant accepts suite balance payments.');
  }

  public function getConfigureInstructions() {
    return pht('This provider does not require any special configuration.');
  }

  public function canRunConfigurationTest() {
    return false;
  }

  public function getPaymentMethodDescription() {
    return pht('Add Suite Balance');
  }

  public function getPaymentMethodIcon() {
    return 'TestPayment';
  }

  public function getPaymentMethodProviderDescription() {
    return pht('Refactory Suite Balance');
  }

  public function getDefaultPaymentMethodDisplayName(
    PhortunePaymentMethod $method) {
    return pht('Suite Balance');
  }

  protected function executeCharge(
    PhortunePaymentMethod $payment_method,
    PhortuneCharge $charge) {

    $cart_phid = $charge->getCartPHID();
    $acc_phid = $payment_method->getAccountPHID();

    $cart = id(new PhortuneCartQuery())
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->withPHIDs(array($cart_phid))
            ->needPurchases(true)
            ->executeOne();

    $account = id(new PhortuneAccountQuery())
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->withPHIDs(array($acc_phid))
            ->executeOne();

    $actor_phids = $account->getMemberPHIDs();
    $acc_members = id(new PhabricatorPeopleQuery())
              ->setViewer(PhabricatorUser::getOmnipotentUser())
              ->withPHIDs($actor_phids)
              ->execute();
    $actor = head($acc_members);
    $balance = SuiteBalanceQuery::loadBalanceForUserAccount(
      $actor, $account, PhabricatorContentSource::newForSource(
        SuiteContentSource::SOURCECONST));


    $price = $cart->getTotalPriceAsCurrency();

    $balance->sub($actor, PhabricatorContentSource::newForSource(
      SuiteContentSource::SOURCECONST), $price->formatBareValue(), false,
      $cart->getName(),
    $cart->getPHID());
  }

  protected function executeRefund(
    PhortuneCharge $charge,
    PhortuneCharge $refund) {

    $cart_phid = $refund->getCartPHID();
    $cart = id(new PhortuneCartQuery())
            ->setViewer(PhabricatorUser::getOmnipotentUser())
            ->withPHIDs(array($cart_phid))
            ->needPurchases(true)
            ->executeOne();

    $account = $cart->getAccount();
    $actor_phids = $account->getMemberPHIDs();
    $acc_members = id(new PhabricatorPeopleQuery())
              ->setViewer(PhabricatorUser::getOmnipotentUser())
              ->withPHIDs($actor_phids)
              ->execute();
    $actor = head($acc_members);

    $balance = SuiteBalanceQuery::loadBalanceForUserAccount(
      $actor, $account, PhabricatorContentSource::newForSource(
        SuiteContentSource::SOURCECONST));

    $refunded = -((int)$refund->getAmountAsCurrency()->formatBareValue());

    $balance->add($actor, PhabricatorContentSource::newForSource(
      SuiteContentSource::SOURCECONST), $refunded, false, pht(
        'Refund of %s', $cart->getName()),
      $cart->getPHID());
  }

  public function updateCharge(PhortuneCharge $charge) {
    $cart = $charge->getCart();

    $transaction_dao = new SuiteBalanceTransaction();
    $existing_charge = $transaction_dao->loadOneWhere('cartPHID = %s',
      $cart->getPHID());

    if (!$existing_charge
        && $cart->getStatus() != PhortuneCart::STATUS_PURCHASED) {
      // This is failed charge, mark it properly
      $cart->didFailCharge($charge);
    }
  }

  public function getAllConfigurableProperties() {
    return array();
  }

  public function getAllConfigurableSecretProperties() {
    return array();
  }

  public function processEditForm(
    AphrontRequest $request,
    array $values) {

    $errors = array();
    $issues = array();
    $values = array();

    return array($errors, $issues, $values);
  }

  public function extendEditForm(
    AphrontRequest $request,
    AphrontFormView $form,
    array $values,
    array $issues) {
    return;
  }



/* -(  Adding Payment Methods  )--------------------------------------------- */


  public function canCreatePaymentMethods() {
    return true;
  }


  public function translateCreatePaymentMethodErrorCode($error_code) {
    return $error_code;
  }


  public function getCreatePaymentMethodErrorMessage($error_code) {
    return null;
  }


  public function validateCreatePaymentMethodToken(array $token) {
    return true;
  }


  public function createPaymentMethodFromRequest(
    AphrontRequest $request,
    PhortunePaymentMethod $method,
    array $token) {
    $phid = $request->getStr('userPHID');
    $last4 = substr($phid, -4);
    $method
      ->setExpires('2050', '01')
      ->setBrand('Suite Balance')
      ->setLastFourDigits($last4)
      ->setMetadata(
        array(
          'type' => 'suite.balance',
        ));

    return array();
  }


  /**
   * @task addmethod
   */
  public function renderCreatePaymentMethodForm(
    AphrontRequest $request,
    array $errors) {

    $ccform = id(new SuiteBalanceForm())
      ->setSecurityAssurance(
        pht('This is an internal provider.'))
      ->setUser($request->getUser())
      ->setErrors($errors);

    Javelin::initBehavior(
      'test-payment-form',
      array(
        'formID' => $ccform->getFormID(),
      ));

    return $ccform->buildForm();
  }
}
