<?php

final class SuiteStoryPointCart
   extends PhortuneCartImplementation {

  private $revisionPHID;
  private $revision;

  public function setRevisionPHID($revision_phid) {
    $this->revisionPHID = $revision_phid;
    return $this;
  }

  public function getRevisionPHID() {
    return $this->revisionPHID;
  }

  public function setRevision(DifferentialRevision $revision) {
    $this->revision = $revision;
    return $this;
  }

  public function getRevision() {
    return $this->revision;
  }

  public function getName(PhortuneCart $cart) {
    $revision = $this->getRevision();
    return pht('[Story Point] %s/D%s',
      rtrim(PhabricatorEnv::getEnvConfig(
        'phabricator.base-uri',
        'https://dashboard.refactory.id'), '/'),
      $revision->getID());
  }

  public function willCreateCart(
    PhabricatorUser $viewer,
    PhortuneCart $cart) {

    $revision = $this->getRevision();
    if (!$revision) {
      throw new PhutilInvalidStateException('setRevision');
    }

    $cart->setMetadataValue('revisionPHID', $revision->getPHID());
  }

  public function loadImplementationsForCarts(
    PhabricatorUser $viewer,
    array $carts) {

    $phids = array();
    foreach ($carts as $cart) {
      $phids[] = $cart->getMetadataValue('revisionPHID');
    }

    $revisions = id(new DifferentialRevisionQuery())
      ->setViewer($viewer)
      ->withPHIDs($phids)
      ->execute();
    $revisions = mpull($revisions, null, 'getPHID');

    $objects = array();
    foreach ($carts as $key => $cart) {
      $revision_phid = $cart->getMetadataValue('revisionPHID');
      $revision = idx($revisions, $revision_phid);
      if (!$revision) {
        continue;
      }

      $object = id(new self())
        ->setRevisionPHID($revision_phid)
        ->setRevision($revision);

      $objects[$key] = $object;
    }

    return $objects;
  }

  public function getCancelURI(PhortuneCart $cart) {
    return '/phortune';
  }

  public function getDoneURI(PhortuneCart $cart) {
    return '/phortune';
  }

  public function getDoneActionName(PhortuneCart $cart) {
    return pht('Return to billing');
  }

}
