<?php

final class JobPosting
  extends JobDAO
  implements
    PhabricatorPolicyInterface,
    PhabricatorApplicationTransactionInterface,
    PhabricatorSubscribableInterface,
    PhabricatorMentionableInterface,
    PhabricatorProjectInterface,
    PhabricatorTokenReceiverInterface,
    PhabricatorFlaggableInterface,
    PhabricatorSpacesInterface,
    PhabricatorConduitResultInterface,
    PhabricatorDestructibleInterface,
    PhabricatorFulltextInterface,
    PhabricatorFerretInterface {


  protected $creatorPHID;
  protected $mailKey;
  protected $viewPolicy;
  protected $icon;
  protected $editPolicy;
  protected $name;
  protected $location;
  protected $isCancelled;
  protected $isLead;
  protected $description;
  protected $salaryFrom;
  protected $salaryTo;
  protected $salaryCurrency;
  protected $benefit;
  protected $perk;
  protected $targetHiring;
  protected $business;
  protected $stack;
  protected $spacePHID;
  protected $utcInitialEpoch;
  protected $utcUntilEpoch;
  protected $utcInstanceEpoch;
  protected $parameters = array();

  private $subscriberPHIDs = self::ATTACHABLE;
  private $rsvps = self::ATTACHABLE;
  private $techStack = self::ATTACHABLE;
  private $applicants = self::ATTACHABLE;

  private $viewerTimezone;
  private $actor;

  const STATUS_LEAD = 'lead';
  const STATUS_LEGIT = 'legitimate';
  const STATE_ACTIVE = 'open';
  const STATE_ARCHIVED = 'closed';

  const CURRENCY_IDR = 'IDR';
  const CURRENCY_USD = 'USD';

  public static function newDefaultEventDateTimes(
    PhabricatorUser $viewer,
    $now) {
    $datetime_start = PhutilCalendarAbsoluteDateTime::newFromEpoch(
      $now,
      $viewer->getTimezoneIdentifier());

    // Advance the time by an hour, then round downwards to the nearest hour.
    // For example, if it is currently 3:25 PM, we suggest a default start time
    // of 4 PM.
    $datetime_start = $datetime_start
      ->newRelativeDateTime('PT1H')
      ->newAbsoluteDateTime();
    $datetime_start->setMinute(0);
    $datetime_start->setSecond(0);

    // Default the end time to an hour after the start time.
    $datetime_end = $datetime_start
      ->newRelativeDateTime('PT1H')
      ->newAbsoluteDateTime();

    return array($datetime_start, $datetime_end);
  }

  public static function getStatusMap() {
    return array(
      self::STATUS_LEAD => array(
        'color' => 'grey',
        'name' => pht('Lead')
      ),
      self::STATUS_LEGIT => array(
        'color' => 'blue',
        'name' => pht('Legitimate')
      ),
    );
  }

  public static function getStatusNameMap() {
    return array(
      self::STATUS_LEAD => pht('Lead'),
      self::STATUS_LEGIT => pht('Legitimate'),
    );
  }

  public static function getStateNameMap() {
    return array(
      self::STATE_ACTIVE => pht('Open'),
      self::STATE_ARCHIVED => pht('Closed'),
    );
  }

  public static function getCurrencyMap() {
    return array(
      self::CURRENCY_IDR => pht('Rupiah'),
      self::CURRENCY_USD => pht('Dollar'),
    );
  }

  public static function initializeNewItem(PhabricatorUser $actor) {

    $app = id(new PhabricatorApplicationQuery())
      ->setViewer($actor)
      ->withClasses(array('PhabricatorJobApplication'))
      ->executeOne();

    $view_policy = $app->getPolicy(JobDefaultViewCapability::CAPABILITY);
    $edit_policy = $app->getPolicy(JobDefaultEditCapability::CAPABILITY);

    $now = PhabricatorTime::getNow();
    $datetime_defaults = self::newDefaultEventDateTimes(
      $actor,
      $now);
    list($datetime_start, $datetime_end) = $datetime_defaults;

    $default_icon = 'fa-file-code-o';

    return id(new JobPosting())
      ->setCreatorPHID($actor->getPHID())
      ->setIsCancelled(0)
      ->setIsLead(1)
      ->setIcon($default_icon)
      ->setViewPolicy($view_policy)
      ->setEditPolicy($edit_policy)
      ->setSpacePHID($actor->getDefaultSpacePHID())
      ->setStartDateTime($datetime_start)
      ->setEndDateTime($datetime_end)
      ->setDescription('')
      ->setLocation('Indonesia, Jakarta Pusat')
      ->setSalaryFrom(0)
      ->setSalaryTo(0)
      ->setBenefit('')
      ->setPerk('')
      ->setTargetHiring(0)
      ->setBusiness('Jasa Pengiriman Barang');
  }

  protected function getConfiguration() {
    return array(
      self::CONFIG_AUX_PHID => true,
      self::CONFIG_COLUMN_SCHEMA => array(
        'name' => 'text',
        'description' => 'text',
        'mailKey' => 'bytes20',
        'location' => 'text64',
        'isLead' => 'bool',
        'icon' => 'text32',
        'isCancelled' => 'bool',
        'salaryFrom' => 'uint32?',
        'salaryTo' => 'uint32?',
        'salaryCurrency' => 'text32',
        'benefit' => 'text?',
        'perk' => 'text?',
        'targetHiring' => 'uint32?',
        'business' => 'text64?',
        'stack' => 'text64?',
        'utcInitialEpoch' => 'epoch',
        'utcUntilEpoch' => 'epoch?',
        'utcInstanceEpoch' => 'epoch?',
      ),
      self::CONFIG_KEY_SCHEMA => array(
        'key_space' => array(
          'columns' => array('spacePHID'),
        ),
        'key_epoch' => array(
          'columns' => array('utcInitialEpoch', 'utcUntilEpoch'),
        ),
      ),
      self::CONFIG_SERIALIZATION => array(
        'parameters' => self::SERIALIZATION_JSON,
      ),
    ) + parent::getConfiguration();
  }

  public function generatePHID() {
    return PhabricatorPHID::generateNewPHID(
      JobPostingPHIDType::TYPECONST);
  }

  public function attachSubscriberPHIDs(array $phids) {
    $this->subscriberPHIDs = $phids;
    return $this;
  }

  public function getViewURI() {
    return '/job/view/'.$this->getID().'/';
  }

  public function setActor(PhabricatorUser $actor) {
    $this->actor = $actor;
    return $this;
  }

  public function getActor() {
    return $this->actor;
  }

  public function updateUTCEpochs() {
    // The "intitial" epoch is the start time of the event, in UTC.
    $start_date = $this->newStartDateTime()
      ->setViewerTimezone('UTC');
    $start_epoch = $start_date->getEpoch();
    $this->setUTCInitialEpoch($start_epoch);

    // The "until" epoch is the last UTC epoch on which any instance of this
    // event occurs. For infinitely recurring events, it is `null`.

    $end_date = $this->newEndDateTime()
    ->setViewerTimezone('UTC');
    $until_epoch = $end_date->getEpoch();
    $this->setUTCUntilEpoch($until_epoch);

    return $this;
  }

  public function save() {
    if (!$this->getMailKey()) {
      $this->setMailKey(Filesystem::readRandomCharacters(20));
    }

    $this->updateUTCEpochs();
    return parent::save();
  }

  public function isArchived() {
    return $this->getIsCancelled();
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
    return new PhabricatorJobPostingEditor();
  }

  public function getApplicationTransactionTemplate() {
    return new JobPostingTransaction();
  }

  public function newStartDateTime() {
    $datetime = $this->getParameter('startDateTime');
    return $this->newDateTimeFromDictionary($datetime);
  }

  public function getStartDateTimeEpoch() {
    return $this->newStartDateTime()->getEpoch();
  }

  public function newEndDateTimeForEdit() {
    $datetime = $this->getParameter('endDateTime');
    return $this->newDateTimeFromDictionary($datetime);
  }

  public function newEndDateTime() {
    $datetime = $this->newEndDateTimeForEdit();

    return $datetime;
  }

  public function getEndDateTimeEpoch() {
    return $this->newEndDateTime()->getEpoch();
  }

  public function newUntilDateTime() {
    $datetime = $this->getParameter('untilDateTime');
    if ($datetime) {
      return $this->newDateTimeFromDictionary($datetime);
    }

    return null;
  }

  public function getUntilDateTimeEpoch() {
    $datetime = $this->newUntilDateTime();

    if (!$datetime) {
      return null;
    }

    return $datetime->getEpoch();
  }

  public function newDuration() {
    return id(new PhutilCalendarDuration())
      ->setSeconds($this->getDuration());
  }

  public function newInstanceDateTime() {
    $index = $this->getSequenceIndex();
    if (!$index) {
      return null;
    }
    return $this->newSequenceIndexDateTime($index);
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

  public function getParameter($key, $default = null) {
    return idx($this->parameters, $key, $default);
  }

  public function setParameter($key, $value) {
    $this->parameters[$key] = $value;
    return $this;
  }

  public function setStartDateTime(PhutilCalendarDateTime $datetime) {
    return $this->setParameter(
      'startDateTime',
      $datetime->newAbsoluteDateTime()->toDictionary());
  }

  public function setEndDateTime(PhutilCalendarDateTime $datetime) {
    return $this->setParameter(
      'endDateTime',
      $datetime->newAbsoluteDateTime()->toDictionary());
  }

  public function setUntilDateTime(PhutilCalendarDateTime $datetime = null) {
    if ($datetime) {
      $value = $datetime->newAbsoluteDateTime()->toDictionary();
    } else {
      $value = null;
    }

    return $this->setParameter('untilDateTime', $value);
  }

  public function hasTechStack() {
    return $this->techStack != self::ATTACHABLE;
  }

  public function attachTechStack(JobPostingTechStack $tech_stack) {
    $this->techStack = $tech_stack;
    return $this;
  }

  public function getTechStack() {
    return $this->assertAttached($this->techStack);
  }

  public function attachApplicants(array $applicants) {
    assert_instances_of($applicants, 'JobPostingApplicant');
    $this->applicants = $applicants;
    return $this;
  }

  public function getApplicants() {
    return $this->assertAttached($this->applicants);
  }

  private function getCoursepathItem() {
    $job_tech_stack = array();
    if (!$this->hasTechStack()) {
      $tech_stack = id(new JobTechStackQuery())
          ->setViewer(PhabricatorUser::getOmnipotentUser())
          ->withPostingPHIDs(array($this->getPHID()))
          ->executeOne();
      if ($tech_stack) {
        $this->attachTechStack($tech_stack);
        $coursepath = id(new CoursepathItemQuery())
              ->setViewer(PhabricatorUser::getOmnipotentUser())
              ->withPHIDs(array(
                  $this->getTechStack()->getCoursepathItemPHID(),
                  ))
              ->executeOne();
        $job_tech_stack = $coursepath;
      }
    }

    return $job_tech_stack;
  }

/* -(  PhabricatorSubscribableInterface  )----------------------------------- */


  public function isAutomaticallySubscribed($phid) {
    return false;
  }


/* -(  PhabricatorTokenReceiverInterface  )---------------------------------- */


  public function getUsersToNotifyOfTokenGiven() {
    return array($this->getCreatorPHID());
  }


/* -(  PhabricatorDestructibleInterface  )----------------------------------- */

  public function destroyObjectPermanently(
    PhabricatorDestructionEngine $engine) {

    $enrollments = id(new JobInviteQuery())
      ->setViewer($engine->getViewer())
      ->withPostingPHIDs(array($this->getPHID()))
      ->execute();

    foreach ($enrollments as $enroll) {
      $engine->destroyObject($enroll);
    }

    $notifications = id(new JobNotification())->loadAllWhere(
      'postingPHID = %s',
      $this->getPHID());
    foreach ($notifications as $notification) {
      $notification->delete();
    }

    $this->openTransaction();
    $this->delete();
    $this->saveTransaction();
  }

/* -(  PhabricatorSpacesInterface  )----------------------------------------- */


public function getSpacePHID() {
  return $this->spacePHID;
}

/* -(  PhabricatorFulltextInterface  )--------------------------------------- */


  public function newFulltextEngine() {
    return new JobPostingFulltextEngine();
  }


/* -(  PhabricatorFerretInterface  )----------------------------------------- */


  public function newFerretEngine() {
    return new JobPostingFerretEngine();
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
    $coursepath = $this->getCoursepathItem();
    $coursepath_name = null;
    if ($coursepath) {
      $coursepath_name = $coursepath->getName();
    }

    $is_applied = false;
    $applicant = id(new JobInviteQuery())
      ->setViewer($this->actor)
      ->withPostingPHIDs(array($this->getPHID()))
      ->withApplicantPHIDs(array($this->actor->getPHID()))
      ->executeOne();

    if ($applicant) {
      $is_applied = true;
    }

    $human_dformat = 'd F Y';

    $start_date_initial = phabricator_datetime(
        $this->getUtcInitialEpoch(),
        $this->actor);

    $end_date_initial = phabricator_datetime(
      $this->getUtcUntilEpoch(),
      $this->actor);

    $start_date = date($human_dformat, strtotime($start_date_initial));
    $end_date = date($human_dformat, strtotime($end_date_initial));


    $engine = PhabricatorMarkupEngine::getEngine()
                  ->setConfig('viewer', PhabricatorUser::getOmnipotentUser());
    $parsed_description = $engine->markupText($this->getDescription());
    if ($parsed_description instanceof PhutilSafeHTML) {
      $parsed_description = $parsed_description->getHTMLContent();
    }

    return array(
      'name' => $this->getName(),
      'description' => $this->getDescription(),
      'htmlDescription' => $parsed_description,
      'creatorPHID' => $this->getCreatorPHID(),
      'isLead' => $this->getIsLead(),
      'location' => $this->getLocation(),
      'salaryCurrency' => $this->getSalaryCurrency(),
      'salaryFrom' => (int)$this->getSalaryFrom(),
      'salaryTo' => (int)$this->getSalaryTo(),
      'targetHiring' => (int)$this->getTargetHiring(),
      'startDate' => $start_date,
      'endDate' => $end_date,
      'techStack' => array(
        'coursepath' => $coursepath_name,
        'stack' => $this->getStack(),
      ),
      'business' => $this->getBusiness(),
      'isApplied' => $is_applied,
    );
  }

  public function getConduitSearchAttachments() {
    return array();
  }
}
