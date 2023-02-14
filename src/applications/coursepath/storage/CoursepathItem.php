<?php

final class CoursepathItem
  extends CoursepathDAO
  implements
    PhabricatorPolicyInterface,
    PhabricatorApplicationTransactionInterface,
    PhabricatorSubscribableInterface,
    PhabricatorConduitResultInterface,
    PhabricatorDestructibleInterface,
    PhabricatorNgramsInterface {


  protected $creatorPHID;
  protected $mailKey;
  protected $editPolicy;
  protected $name;
  protected $description;
  protected $status;
  protected $slug;

  private $tracks = self::ATTACHABLE;
  private $enrollments = self::ATTACHABLE;
  private $subscriberPHIDs = self::ATTACHABLE;

  const STATUS_ACTIVE = 'open';
  const STATUS_ARCHIVED = 'closed';

  public static function getStatusNameMap() {
    return array(
      self::STATUS_ACTIVE => pht('Active'),
      self::STATUS_ARCHIVED => pht('Archived'),
    );
  }

  public static function initializeNewItem(PhabricatorUser $actor) {

    $app = id(new PhabricatorApplicationQuery())
      ->setViewer($actor)
      ->withClasses(array('PhabricatorCoursepathApplication'))
      ->executeOne();

    $edit_policy = $app->getPolicy(CoursepathDefaultEditCapability::CAPABILITY);

    return id(new CoursepathItem())
      ->setCreatorPHID($actor->getPHID())
      ->setEditPolicy($edit_policy)
      ->setDescription('')
      ->setStatus(self::STATUS_ACTIVE);
  }

  protected function getConfiguration() {
    return array(
      self::CONFIG_AUX_PHID => true,
      self::CONFIG_COLUMN_SCHEMA => array(
        'name' => 'sort255',
        'description' => 'text',
        'mailKey' => 'bytes20',
        'status' => 'text32',
        'slug'  => 'text32?',
      ),
      self::CONFIG_KEY_SCHEMA => array(
        'key_creator' => array(
          'columns' => array('creatorPHID', 'dateModified'),
        ),
      ),
    ) + parent::getConfiguration();
  }

  public function generatePHID() {
    return PhabricatorPHID::generateNewPHID(
      CoursepathItemPHIDType::TYPECONST);
  }

  public function attachSubscriberPHIDs(array $phids) {
    $this->subscriberPHIDs = $phids;
    return $this;
  }

  public function getViewURI() {
    return '/coursepath/item/view/'.$this->getID().'/';
  }

  public function save() {
    if (!$this->getMailKey()) {
      $this->setMailKey(Filesystem::readRandomCharacters(20));
    }
    return parent::save();
  }

  public function isArchived() {
    return ($this->getStatus() == self::STATUS_ARCHIVED);
  }

  public function getTracks() {
    return $this->assertAttached($this->tracks);
  }

  public function attachTracks(array $tracks) {
    assert_instances_of($tracks, 'CoursepathItemTrack');
    $this->tracks = $tracks;
    return $this;
  }

  public function getEnrollments() {
    return $this->assertAttached($this->enrollments);
  }

  public function attachEnrollments(array $enrollments) {
    assert_instances_of($enrollments, 'CoursepathItemEnrollment');
    $this->enrollments = $enrollments;
    return $this;
  }

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
    return $viewer->getPHID() == $this->creatorPHID;
  }

  public function describeAutomaticCapability($capability) {
    switch ($capability) {
      case PhabricatorPolicyCapability::CAN_VIEW:
        return pht('Owners of an item can always view it.');
      case PhabricatorPolicyCapability::CAN_EDIT:
        return pht('Owners of an item can always edit it.');
    }
    return null;
  }

  public function getApplicationTransactionEditor() {
    return new PhabricatorCoursepathItemEditor();
  }

  public function getApplicationTransactionTemplate() {
    return new CoursepathTransaction();
  }

/* -(  PhabricatorSubscribableInterface  )----------------------------------- */


  public function isAutomaticallySubscribed($phid) {
    return false;
  }

/* -(  PhabricatorDestructibleInterface  )----------------------------------- */

  public function destroyObjectPermanently(
    PhabricatorDestructionEngine $engine) {

    $enrollments = id(new CoursepathItemEnrollmentQuery())
      ->setViewer($engine->getViewer())
      ->withItemPHIDs(array($this->getPHID()))
      ->execute();

    foreach ($enrollments as $enroll) {
      $engine->destroyObject($enroll);
    }

    $this->openTransaction();
      $this->delete();
    $this->saveTransaction();
  }

/* -(  PhabricatorConduitResultInterface  )---------------------------------- */


  public function getFieldSpecificationsForConduit() {
    return array(
      id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('name')
        ->setType('string')
        ->setDescription(pht('The name of the course path.')),
      id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('creatorPHID')
        ->setType('phid')
        ->setDescription(pht('User PHID of the creator.')),
      id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('status')
        ->setType('string')
        ->setDescription(pht('Active or archived status of the course path.')),
    );
  }

  public function getFieldValuesForConduit() {
     return array(
      'name' => $this->getName(),
      'description' => $this->getDescription(),
      'creatorPHID' => $this->getCreatorPHID(),
      'status' => $this->getStatus(),
      'slug'   => $this->getSlug(),
    );
  }

  public function getConduitSearchAttachments() {
    return array();
  }

/* -(  PhabricatorNgramInterface  )------------------------------------------ */


  public function newNgrams() {
    return array(
      id(new CoursepathItemNameNgrams())
        ->setValue($this->getName()),
    );
  }
}
