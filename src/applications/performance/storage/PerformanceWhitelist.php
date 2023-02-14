<?php

final class PerformanceWhitelist
  extends PerformanceDAO
  implements
    PhabricatorPolicyInterface,
    PhabricatorApplicationTransactionInterface,
    PhabricatorSubscribableInterface,
    PhabricatorMentionableInterface,
    PhabricatorConduitResultInterface {


  protected $ownerPHID;
  protected $targetPHID;
  protected $viewPolicy;
  protected $editPolicy;
  protected $isActive = 1;
  protected $note;

  private $owner = null;

  private $subscriberPHIDs = self::ATTACHABLE;

  protected function readField($field) {
    switch ($field) {
      // Make sure these return booleans.
      case 'isActive':
        return (bool)$this->isActive;
      default:
        return parent::readField($field);
    }
  }

  public function getTableName() {
    return sev2table('whitelist');
  }

  public static function addNewTarget(
    PhabricatorUser $actor,
    PhabricatorUser $target,
    PhabricatorContentSource $content_source) {

    $whitelist = self::initializeNewEntry($actor, $target, $content_source);

    $xactions = array();
    $xactions[] = id(new PerformanceWhitelistTransaction())
      ->setTransactionType(
        PerformanceWhitelistIsActiveTransaction::TRANSACTIONTYPE)
      ->setNewValue(true);

    $editor = id(new PerformanceWhitelistEditor())
      ->setActor($actor)
      ->setContentSource($content_source)
      ->setContinueOnNoEffect(true);

    // We create an profile for you the first time you visit Suite.
    $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();

      $editor->applyTransactions($whitelist, $xactions);

      $whitelist->save();

    unset($unguarded);

    return $whitelist;
  }

  public static function initializeNewEntry(PhabricatorUser $actor,
    PhabricatorUser $target, $content_source) {

    $app = id(new PhabricatorApplicationQuery())
      ->setViewer($actor)
      ->withClasses(array('PhabricatorPerformanceApplication'))
      ->executeOne();

    $view_policy = $app->getPolicy(
      PerformanceManageCapability::CAPABILITY);
    $edit_policy = $app->getPolicy(
      PerformanceManageCapability::CAPABILITY);

    return id(new self())
      ->setOwnerPHID($actor->getPHID())
      ->setIsActive(1)
      ->setNote('undefined')
      ->setTargetPHID($target->getPHID())
      ->setViewPolicy($view_policy)
      ->setEditPolicy($edit_policy);
  }

  protected function getConfiguration() {
    return array(
      self::CONFIG_AUX_PHID => true,
      self::CONFIG_COLUMN_SCHEMA => array(
        'isActive' => 'bool',
        'note' => 'text64',
        'targetPHID' => 'phid',
      ),
      self::CONFIG_KEY_SCHEMA => array(
        'key_owner' => array(
          'columns' => array('ownerPHID'),
        ),
      ),
    ) + parent::getConfiguration();
  }

  public function generatePHID() {
    return PhabricatorPHID::generateNewPHID(
      PerformancePipPHIDType::TYPECONST);
  }

  public function attachSubscriberPHIDs(array $phids) {
    $this->subscriberPHIDs = $phids;
    return $this;
  }


  public function getOwner() {
    return $this->assertAttached($this->owner);
  }

  public function attachOwner(PhabricatorUser $owner) {
    $this->owner = $owner;
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

  public function getViewURI() {
    return '/performance/';
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
    return $viewer->getPHID() == $this->ownerPHID;
  }

  public function describeAutomaticCapability($capability) {
    switch ($capability) {
      case PhabricatorPolicyCapability::CAN_VIEW:
        return pht('Manager can always view it.');
      case PhabricatorPolicyCapability::CAN_EDIT:
        return pht('Manager can always edit it.');
    }
    return null;
  }

  public function getApplicationTransactionEditor() {
    return new PerformanceWhitelistEditor();
  }

  public function getApplicationTransactionTemplate() {
    return new PerformanceWhitelistTransaction();
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
        ->setDescription(pht('User PHID of the manager.')),
    );
  }

  public function getFieldValuesForConduit() {
    return array(
      'ownerPHID' => $this->getOwnerPHID(),
      'iActive' => $this->getIsActive(),
      'note' => $this->getNote(),
    );
  }

  public function getConduitSearchAttachments() {
    return array();
  }
}
