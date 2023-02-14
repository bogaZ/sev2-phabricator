<?php

final class SuiteBillStoryPointWorker
  extends PhabricatorWorker {

  protected function doWork() {
    $data = $this->getTaskData();
    $viewer = PhabricatorUser::getOmnipotentUser();

    $story_point = idx($data, 'storyPoint');
    $revision_phid = idx($data, 'revisionPHID');
    $project_phid = idx($data, 'projectPHID');
    $content_source = PhabricatorContentSource::newForSource(
      SuiteContentSource::SOURCECONST);

    $spec = id(new PhabricatorProjectRspSpecQuery())
                ->withProjectPHIDs(array($project_phid))
                ->setViewer($viewer)
                ->executeOne();

    if ($spec) {
      $actor = id(new PhabricatorPeopleQuery())
                ->setViewer($viewer)
                ->withPHIDs(array($spec->getBillingUserPHID()))
                ->executeOne();

      $accounts = PhortuneAccountQuery::loadAccountsForUser(
                      $actor,
                      $content_source);
      $account = head($accounts);
      $merchant = id(new PhortuneMerchantQuery())
                    ->setViewer($viewer)
                    ->executeOne();

      $method = id(new PhortunePaymentMethodQuery())
        ->setViewer($viewer)
        ->withAccountPHIDs(array($account->getPHID()))
        ->withStatuses(
          array(
            PhortunePaymentMethod::STATUS_ACTIVE,
          ))
        ->executeOne();

      // Get product
      $product = id(new PhortuneProductQuery())
        ->setViewer($viewer)
        ->withClassAndRef('SuiteStoryPointProduct', 'SP')
        ->executeOne();

      // Prepare product & cart impl
      $revision = id(new DifferentialRevisionQuery())
                  ->withPHIDs(array($revision_phid))
                  ->setViewer($viewer)
                  ->executeOne();
      $cart_implementation = id(new SuiteStoryPointCart())
                              ->setRevisionPHID($revision->getPHID())
                              ->setRevision($revision);

      $cart = $account->newCart($actor, $cart_implementation, $merchant);

      // Get Product SP value & currency
      $sp_currency = PhortuneCurrency::newFromValueAndCurrency(
        $spec->getStoryPointBilledValue(),
        $spec->getStoryPointCurrency());

      $purchase = $cart->newPurchase($actor, $product);
      $purchase
        ->setBasePriceAsCurrency($sp_currency)
        ->setQuantity((int)$story_point)
        ->setMetadataValue('DifferentialRevisionPHID', $revision->getPHID())
        ->save();

      $cart
        ->setIsInvoice(1)
        ->save();

      $cart->activateCart();

      $err = null;
      try {
        $issues = $this->charge($actor, $cart, $method);
      } catch (Exception $ex) {
        $err = $ex;
      } catch (Throwable $ex) {
        $err = $ex;
      }

      if ($err) {
        $issues = array(
          pht(
            'There was a technical error while trying to automatically bill '.
            'this order: %s',
            $ex),
        );
      }

      if (!$issues) {
        // We're all done; charging the cart sends a billing email as a side
        // effect.
        return;
      }

      // We're shoving this through the CartEditor because it has all the logic
      // for sending mail about carts. This doesn't really affect the state of
      // the cart, but reduces the amount of code duplication.
      $xactions = array();
      $xactions[] = id(new PhortuneCartTransaction())
        ->setTransactionType(PhortuneCartTransaction::TYPE_INVOICED)
        ->setNewValue(true);

      $content_source = PhabricatorContentSource::newForSource(
        PhabricatorPhortuneContentSource::SOURCECONST);

      $acting_phid = id(new PhabricatorPhortuneApplication())->getPHID();
      $editor = id(new PhortuneCartEditor())
        ->setActor($viewer)
        ->setActingAsPHID($acting_phid)
        ->setContentSource($content_source)
        ->setContinueOnMissingFields(true)
        ->setInvoiceIssues($issues)
        ->applyTransactions($cart, $xactions);
    }
  }

  private function charge(
    PhabricatorUser $viewer,
    PhortuneCart $cart,
    PhortunePaymentMethod $method) {

    $issues = array();

    $provider = $method->buildPaymentProvider();

    $charge = $cart->willApplyCharge($viewer, $provider, $method);

    $err = null;
    try {
      $provider->applyCharge($method, $charge);
    } catch (Exception $ex) {
      $err = $ex;
    } catch (Throwable $ex) {
      $err = $ex;
    }

    if ($err) {
      $cart->didFailCharge($charge);
      $issues[] = pht(
        'Automatic billing failed: %s',
        $ex->getMessage());
      return $issues;
    }

    $cart->didApplyCharge($charge);
  }

}
