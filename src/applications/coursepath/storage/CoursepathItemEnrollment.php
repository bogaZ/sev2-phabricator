<?php

final class CoursepathItemEnrollment extends CoursepathDAO
  implements
    PhabricatorDestructibleInterface,
    PhabricatorPolicyInterface {

  protected $itemPHID;
  protected $registrarPHID;
  protected $tutorPHID;

  private $item = self::ATTACHABLE;

  public static function initializeNewEnrollment(
    PhabricatorUser $actor,
    CoursepathItem $item,
    $registrar_phid) {
    return id(new self())
      ->setRegistrarPHID($registrar_phid)
      ->setItemPHID($item->getPHID())
      ->setTutorPHID($actor->getPHID())
      ->attachItem($item);
  }

  protected function getConfiguration() {
    return array(
      self::CONFIG_KEY_SCHEMA => array(
        'key_item' => array(
          'columns' => array('itemPHID', 'registrarPHID'),
          'unique' => true,
        ),
        'key_registrar' => array(
          'columns' => array('registrarPHID'),
        ),
      ),
    ) + parent::getConfiguration();
  }

  public function attachItem(CoursepathItem $item) {
    $this->item = $item;
    return $this;
  }

  public function getItem() {
    return $this->assertAttached($this->item);
  }


/* -(  PhabricatorDestructibleInterface  )----------------------------------- */


  public function destroyObjectPermanently(
    PhabricatorDestructionEngine $engine) {

    $this->openTransaction();
      $this->delete();
    $this->saveTransaction();
  }


/* -(  PhabricatorPolicyInterface  )----------------------------------------- */


  public function getCapabilities() {
    return array(
      PhabricatorPolicyCapability::CAN_VIEW,
      PhabricatorPolicyCapability::CAN_EDIT,
    );
  }

  public function getPolicy($capability) {
    return $this->getItem()->getPolicy($capability);
  }

  public function hasAutomaticCapability($capability, PhabricatorUser $viewer) {
    return false;
  }

}
