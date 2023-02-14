<?php

final class SuiteStoryPointProduct extends PhortuneProductImplementation {

  private $ref;

  public function loadImplementationsForRefs(
    PhabricatorUser $viewer,
    array $refs) {

    $results = array();
    foreach ($refs as $key => $ref) {
      $product = new self();
      $product->setRef($ref);
      $results[$key] = $product;
    }

    return $results;
  }


  public function setRef($ref) {
    $this->ref = $ref;
    return $this;
  }

  public function getRef() {
    return $this->ref;
  }

  public function getName(PhortuneProduct $product) {
    return pht('Suite Story Point');
  }

  public function getPurchaseName(
    PhortuneProduct $product,
    PhortunePurchase $purchase) {

    return coalesce(
      $purchase->getMetadataValue('adhoc.name'),
      $this->getName($product));
  }

  public function getPriceAsCurrency(PhortuneProduct $product) {
    return PhortuneCurrency::newEmptyCurrency();
  }

}
