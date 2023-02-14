<?php

final class CoursepathItemTrack extends CoursepathDAO
  implements
    PhabricatorApplicationTransactionInterface,
    PhabricatorDestructibleInterface,
    PhabricatorSubscribableInterface,
    PhabricatorPolicyInterface {

  protected $creatorPHID;
  protected $itemPHID;
  protected $name;
  protected $description;
  protected $image;
  protected $lecture;
  protected $editPolicy;

  private $enrollments = self::ATTACHABLE;
  private $items = self::ATTACHABLE;

  public static function initializeNewTrack(
    PhabricatorUser $actor,
    $item_phid) {
    $app = id(new PhabricatorApplicationQuery())
      ->setViewer($actor)
      ->withClasses(array('PhabricatorCoursepathApplication'))
      ->executeOne();

    $edit_policy = $app->getPolicy(CoursepathDefaultEditCapability::CAPABILITY);
    return id(new self())
      ->setCreatorPHID($actor->getPHID())
      ->setItemPHID($item_phid)
      ->setEditPolicy($edit_policy);
  }

  protected function getConfiguration() {
    return array(
      self::CONFIG_AUX_PHID => true,
      self::CONFIG_COLUMN_SCHEMA => array(
        'name' => 'sort255',
        'image' => 'sort255',
        'description' => 'text?',
        'lecture' => 'text?',
      ),
      self::CONFIG_KEY_SCHEMA => array(
        'key_creator' => array(
          'columns' => array('creatorPHID', 'dateModified'),
        ),
        'key_item' => array(
          'columns' => array('itemPHID'),
        ),
      ),
    ) + parent::getConfiguration();
  }

  public function generatePHID() {
    return PhabricatorPHID::generateNewPHID(
      CoursepathItemTrackPHIDType::TYPECONST);
  }

  public function attachItems(array $items) {
    assert_instances_of($items, __CLASS__);
    $this->items = $items;
    return $this;
  }

  public function getItem() {
    return $this->assertAttached($this->items);
  }

  public function getEnrollments() {
    return $this->assertAttached($this->enrollments);
  }

  public function attachEnrollments(array $enrollments) {
    assert_instances_of($enrollments, 'CoursepathItemEnrollment');
    $this->enrollments = $enrollments;
    return $this;
  }

  public function getApplicationTransactionEditor() {
    return new PhabricatorCoursepathItemTrackEditor();
  }

  public function getApplicationTransactionTemplate() {
    return new CoursepathItemTrackTransaction();
  }

  public function getViewURI($item_id, $stack_id) {
    $test_id = $this->getID();
    $uri = "/coursepath/item/view/$item_id";
    $view_uri = "$uri/stacks/view/$stack_id/tests/view/$test_id/";
    return id(new PhutilURI($view_uri));
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
    return PhabricatorPolicies::POLICY_USER;
  }

  public function hasAutomaticCapability($capability, PhabricatorUser $viewer) {
    return false;
  }

/* -(  PhabricatorSubscribableInterface  )----------------------------------- */


  public function isAutomaticallySubscribed($phid) {
    return false;
  }

}
