<?php

final class LobbyModerator
  extends LobbyDAO
  implements
    PhabricatorPolicyInterface,
    PhabricatorDestructibleInterface,
    PhabricatorApplicationTransactionInterface,
    PhabricatorConduitResultInterface {


  protected $ownerPHID;
  protected $moderatorPHID;
  protected $channelPHID;
  protected $viewPolicy;
  protected $editPolicy;

  private $owner = null;
  private $moderator = null;
  private $channel = null;

  public static function initializeNewItem(PhabricatorUser $author) {
    $app = id(new PhabricatorApplicationQuery())
      ->setViewer($author)
      ->withClasses(array('PhabricatorLobbyApplication'))
      ->executeOne();

    $view_policy = $app->getPolicy(
      LobbyJoinCapability::CAPABILITY);
    $edit_policy = $app->getPolicy(
      LobbyJoinCapability::CAPABILITY);

    return id(new self())
      ->setOwnerPHID($author->getPHID())
      ->setViewPolicy($view_policy)
      ->setEditPolicy($edit_policy);
  }

  public function getMailKey() {
    return;
  }

  public function setMailKey() {
    return;
  }

  public function getTableName() {
    return sev2table('moderators');
  }

  protected function getConfiguration() {
    return array(
      self::CONFIG_AUX_PHID => true,
      self::CONFIG_COLUMN_SCHEMA => array(
        'channelPHID' => 'phid',
        'moderatorPHID' => 'phid',
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
      LobbyModeratorPHIDType::TYPECONST);
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

    $this->owner = id(new PhabricatorPeopleQuery())
                    ->setViewer(PhabricatorUser::getOmnipotentUser())
                    ->withPHIDs(array($this->getOwnerPHID()))
                    ->needProfile(true)
                    ->needProfileImage(true)
                    ->executeOne();

    return $this->owner;
  }

  public function getModerator() {
    return $this->assertAttached($this->moderator);
  }

  public function attachModerator(PhabricatorUser $moderator) {
    $this->moderator = $moderator;
    return $this;
  }

  public function loadModerator() {
    if ($this->moderator) {
      return $this->moderator;
    }

    $this->moderator = id(new PhabricatorPeopleQuery())
                    ->setViewer(PhabricatorUser::getOmnipotentUser())
                    ->withPHIDs(array($this->getModeratorPHID()))
                    ->needProfile(true)
                    ->needProfileImage(true)
                    ->executeOne();

    return $this->moderator;
  }

  public function getChannel() {
    return $this->assertAttached($this->channel);
  }

  public function attachChannel(ConpherenceThread $channel) {
    $this->channel = $channel;
    return $this;
  }

  public function loadChannel() {
    if ($this->channel) {
      return $this->channel;
    }

    $this->channel = id(new ConpherenceThreadQuery())
                      ->setViewer(PhabricatorUser::getOmnipotentUser())
                      ->withPHIDs(array($this->getChannelPHID()))
                      ->needParticipants(true)
                      ->executeOne();

    return $this->channel;
  }

  public function getViewURI() {
    return '/lobby/moderators/';
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
    return PhabricatorPolicies::POLICY_USER;
  }

  public function hasAutomaticCapability($capability, PhabricatorUser $viewer) {
    return $viewer->getPHID() == $this->ownerPHID;
  }

  public function describeAutomaticCapability($capability) {
    switch ($capability) {
      case PhabricatorPolicyCapability::CAN_VIEW:
        return pht('User can always view it.');
      case PhabricatorPolicyCapability::CAN_EDIT:
        return pht('User can always edit it.');
    }
    return null;
  }

  public function getApplicationTransactionEditor() {
    return new LobbyModeratorEditor();
  }

  public function getApplicationTransactionTemplate() {
    return new LobbyModeratorTransaction();
  }

/* -(  PhabricatorDestructibleInterface  )----------------------------------- */


  public function destroyObjectPermanently(
    PhabricatorDestructionEngine $engine) {

    $this->delete();
  }

/* -(  PhabricatorConduitResultInterface  )---------------------------------- */


  public function getFieldSpecificationsForConduit() {
    return array(
      id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('ownerPHID')
        ->setType('string')
        ->setDescription(pht('User PHID.')),
    );
  }

  public function getFieldValuesForConduit() {
    return array(
      'ownerPHID' => $this->getOwnerPHID(),
      'channelPHID' => $this->getChannelPHID(),
      'moderatorPHID' => $this->getModeratorPHID(),
    );
  }

  public function getConduitSearchAttachments() {
    return array();
  }
}
