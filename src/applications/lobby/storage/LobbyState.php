<?php

final class LobbyState
  extends LobbyDAO
  implements
    PhabricatorPolicyInterface,
    PhabricatorApplicationTransactionInterface,
    PhabricatorConduitResultInterface {


  protected $ownerPHID;
  protected $viewPolicy;
  protected $editPolicy;
  protected $status = 1;
  protected $device = 'phone';
  protected $currentTask;
  protected $currentChannel;
  protected $isWorking = 0;
  protected $dateLastTalk;

  private $parameters = array();
  private $owner = null;
  private $channel = null;

  const DEFAULT_TASK = "Just mingling";
  const DEFAULT_DEVICE = "phone";

  const STATUS_IN_LOBBY = 1;
  const STATUS_IN_CHANNEL = 2;
  const STATUS_BREAK_BATHROOM = 3;
  const STATUS_BREAK_LUNCH = 4;
  const STATUS_BREAK_ME_TIME = 5;
  const STATUS_BREAK_FAMILY = 6;
  const STATUS_BREAK_PRAY = 7;
  const STATUS_BREAK_OTHER = 8;

  public static function getStatusMap() {
    return array(
      self::STATUS_IN_LOBBY => pht('In Lobby'),
      self::STATUS_IN_CHANNEL => pht('In Channel'),
      self::STATUS_BREAK_BATHROOM => pht('Bathroom'),
      self::STATUS_BREAK_LUNCH => pht('Lunch'),
      self::STATUS_BREAK_ME_TIME => pht('Me Time!'),
      self::STATUS_BREAK_FAMILY => pht('Family thing'),
      self::STATUS_BREAK_PRAY => pht('Praying'),
      self::STATUS_BREAK_OTHER => pht('Other'),
    );
  }

  public static function getStatusIconMap() {
    return array(
      self::STATUS_IN_LOBBY => 'fa-coffee',
      self::STATUS_IN_CHANNEL => 'fa-rss',
      self::STATUS_BREAK_BATHROOM => 'fa-bath',
      self::STATUS_BREAK_LUNCH => 'fa-cutlery',
      self::STATUS_BREAK_ME_TIME => 'fa-gamepad',
      self::STATUS_BREAK_FAMILY => 'fa-life-bouy',
      self::STATUS_BREAK_PRAY => 'fa-bell-slash-o',
      self::STATUS_BREAK_OTHER => 'fa-user-secret',
    );
  }

  protected function readField($field) {
    switch ($field) {
      // Make sure these return booleans.
      case 'isWorking':
        return (bool)$this->isWorking;
      default:
        return parent::readField($field);
    }
  }

  public function getMailKey() {
    return;
  }

  public function setMailKey() {
    return;
  }

  public function getTableName() {
    return sev2table('state');
  }

  public static function getCurrent(PhabricatorUser $actor,
    $content_source, $device = 'phone') {

    $all = id(new LobbyStateQuery())
                ->setViewer(PhabricatorUser::getOmnipotentUser())
                ->withOwnerPHIDs(array($actor->getPHID()))
                ->execute();
    $current = head($all);

    if ($current) {
      return $current;
    } else {
      $app = id(new PhabricatorApplicationQuery())
        ->setViewer($actor)
        ->withClasses(array('PhabricatorLobbyApplication'))
        ->executeOne();

      $view_policy = $app->getPolicy(
        LobbyJoinCapability::CAPABILITY);
      $edit_policy = $app->getPolicy(
        LobbyJoinCapability::CAPABILITY);

      return id(new self())
        ->setOwnerPHID($actor->getPHID())
        ->setIsWorking(1)
        ->setStatus(1)
        ->setDevice($device)
        ->setViewPolicy($view_policy)
        ->setEditPolicy($edit_policy);
    }
  }

  protected function getConfiguration() {
    return array(
      self::CONFIG_AUX_PHID => true,
      self::CONFIG_COLUMN_SCHEMA => array(
        'isWorking' => 'bool',
        'device' => 'text64',
        'currentTask' => 'text64?',
        'currentChannel' => 'text64?',
        'status' => 'sint32',
        'dateLastTalk' => 'epoch',
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
      LobbyStatePHIDType::TYPECONST);
  }

  public function getCurrentDevice() {
    return $this->device == 'phone'
            ? 'from Mobile'
            : 'from Desktop';

  }

  public function getStatusIcon() {

    if (!$this->isWorking) {
      return 'fa-bed';
    }

    $maps = self::getStatusIconMap();

    return isset($maps[$this->status])
          ? $maps[$this->status]
          : 'fa-secret';
  }

  public function getStatusText() {

    if (!$this->isWorking) {
      return pht('Not Available');
    }

    $maps = self::getStatusMap();

    return isset($maps[$this->status])
          ? $maps[$this->status]
          : 'Unknown';
  }

  public function getCurrentTaskMarkup() {
    if (empty($this->currentTask)) {
      return self::DEFAULT_TASK;
    }

    $engine = PhabricatorMarkupEngine::getEngine()
                  ->setConfig('viewer', PhabricatorUser::getOmnipotentUser());
    return $engine->markupText($this->currentTask);
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

  public function loadChannel() {
    if ($this->channel) {
      return $this->channel;
    }

    $this->channel = id(new ConpherenceThreadQuery())
                      ->setViewer(PhabricatorUser::getOmnipotentUser())
                      ->withPHIDs(array($this->getCurrentChannel()))
                      ->needParticipants(true)
                      ->executeOne();

    return $this->channel;
  }

  public function newInstanceDateTime() {
    $index = $this->getSequenceIndex();
    if (!$index) {
      return null;
    }
    return $this->newSequenceIndexDateTime($index);
  }

  public function getParameter($key, $default = null) {
    return idx($this->parameters, $key, $default);
  }

  public function setParameter($key, $value) {
    $this->parameters[$key] = $value;
    return $this;
  }

  private function newDateTimeFromEpoch($epoch) {
    $datetime = PhutilCalendarAbsoluteDateTime::newFromEpoch($epoch);

    return $this->newDateTimeFromDateTime($datetime);
  }

  public function applyViewerTimezone(PhabricatorUser $viewer) {
    $this->viewerTimezone = $viewer->getTimezoneIdentifier();
    return $this;
  }

  private function newDateTimeFromDictionary(array $dict) {
    $datetime = PhutilCalendarAbsoluteDateTime::newFromDictionary($dict);
    return $this->newDateTimeFromDateTime($datetime);
  }

  private function newDateTimeFromDateTime(PhutilCalendarDateTime $datetime) {
    $viewer_timezone = $this->viewerTimezone;
    if ($viewer_timezone) {
      $datetime->setViewerTimezone($viewer_timezone);
    }

    return $datetime;
  }

  public function newDateLastTalkForEdit() {
    $datetime = $this->getParameter('lastTalkDateTime');

    if (!$datetime) {
      return PhutilCalendarAbsoluteDateTime::newFromEpoch(time());
    } else {
      return $this->newDateTimeFromDictionary($datetime);
    }
  }

  public function newDateLastTalk() {
    $datetime = $this->newDateLastTalkForEdit();

    return $datetime;
  }

  public function getDateLastTalkEpoch() {
    return $this->newDateLastTalk()->getEpoch();
  }

  public function setDateLastTalkTime(PhutilCalendarDateTime $datetime) {
    return $this->setParameter(
      'lastTalkDateTime',
      $datetime->newAbsoluteDateTime()->toDictionary());
  }

  public function getViewURI() {
    return '/lobby/state';
  }

  public function updateUTCEpochs() {
    if ($this->dateLastTalk == 0) {
      // The "dateLastTalk" epoch is the last time owner speak, in UTC
      $end_date = $this->newDateLastTalk()
      ->setViewerTimezone('UTC');
      $epoch = $end_date->getEpoch();
      $this->setDateLastTalk((string) $epoch);
    }

    return $this;
  }

  public function save() {
    if (!$this->getMailKey()) {
      $this->setMailKey(Filesystem::readRandomCharacters(20));
    }

    $this->updateUTCEpochs();

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
    return new LobbyStateEditor();
  }

  public function getApplicationTransactionTemplate() {
    return new LobbyStateTransaction();
  }

/* -(  PhabricatorConduitResultInterface  )---------------------------------- */

  public function resetTaskEdge() {
    $src_phid = $this->getPHID();
    $edge_type = LobbyHasTaskEdgeType::EDGECONST;

    $editor = new PhabricatorEdgeEditor();

    $dst_phids = PhabricatorEdgeQuery::loadDestinationPHIDs(
      $src_phid,
      $edge_type);

    foreach ($dst_phids as $dst_phid) {
      $editor->removeEdge($src_phid, $edge_type, $dst_phid);
    }

    $editor->save();
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
    $user = $this->getOwner();
    $channel = $this->loadChannel();
    $lobby_state = id(new LobbyStateQuery())
      ->setViewer($user)
      ->withOwnerPHIDs(array($user->getPHID()))
      ->executeOne();
    $statuses = self::getStatusMap();

    return array(
      'ownerPHID' => $this->getOwnerPHID(),
      'isWorking' => $this->getIsWorking(),
      'status' => $this->getStatus(),
      'owner' => array(
        'username' => $user->getFullName(),
        'profile_image_uri' => $user->getProfileImageURI(),
      ),
      'device' => $this->getDevice(),
      'status_icon' => $this->getStatusIcon(),
      'status_text' => $this->getStatusText(),
      'channel' => $channel ? $channel->getTitle() : '-',
      'state' => array(
        'status' => $statuses[$lobby_state->getStatus()],
        'statusIcon' => $this->getStatusIcon(),
        'currentTask' => $lobby_state->getCurrentTask(),
        'currentChannel' => $channel ? $channel->getTitle() : '-',
        'currentChannelPHID' => $lobby_state->getCurrentChannel(),
      ),
    );
  }

  public function getConduitSearchAttachments() {
    return array();
  }
}
