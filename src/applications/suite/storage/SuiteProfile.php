<?php

final class SuiteProfile
  extends SuiteDAO
  implements
    PhabricatorPolicyInterface,
    PhabricatorCustomFieldInterface,
    PhabricatorApplicationTransactionInterface,
    PhabricatorSubscribableInterface,
    PhabricatorMentionableInterface,
    PhabricatorConduitResultInterface {


  protected $ownerPHID;
  protected $mailKey;
  protected $viewPolicy;
  protected $editPolicy;
  protected $isRsp = 0;
  protected $isEligibleForJob = 0;
  protected $upFor;
  protected $graduationTargetMonth;
  protected $identityDocPHID;
  protected $taxDocPHID;
  protected $familyDocPHID;
  protected $skckDocPHID;
  protected $domicileDocPHID;
  protected $certificateDocPHID;
  protected $otherDocPHID;
  protected $additionalDocPHID;
  protected $cv = array();

  private $owner = null;

  private $subscriberPHIDs = self::ATTACHABLE;
  private $customFields = self::ATTACHABLE;

  const UP_FOR_WORK = 'work';
  const UP_FOR_RSP = 'rsp';
  const UP_FOR_UPSKILL = 'upskill';

  const DOC_IDENTITY = 'identityDoc';
  const DOC_TAX = 'taxDoc';
  const DOC_SKCK = 'skckDoc';
  const DOC_FAMILY = 'familyDoc';
  const DOC_DOMICILE = 'domicileDoc';
  const DOC_CERTIFICATE = 'certificateDoc';
  const DOC_OTHER = 'otherDoc';
  const DOC_ADDITIONAL = 'additionalDoc';

  public static function getUpForMap() {
    return array(
      self::UP_FOR_WORK => pht('Saya ingin bekerja di '
        .'perusahaan mitra Refactory'),
      self::UP_FOR_RSP => pht('Saya ingin menjadi Refactory '
        .'Strategic Partner'),
      self::UP_FOR_UPSKILL => pht('Saya ingin meningkatkan skill '
        .'untuk menunjang pekerjaan saya'),
    );
  }

  public static function getTNCMap() {
    $legals = id(new LegalpadDocumentQuery())
              ->setViewer(PhabricatorUser::getOmnipotentUser())
              ->execute();

    $docs = array();
    foreach ($legals as $legal) {
      $docs[$legal->getMonogram()] = $legal->getTitle();
    }

    return $docs;
  }

  public static function getSignaturesMap() {
    return array(
      self::UP_FOR_RSP => array('L5', 'L7', 'L8'),
      self::UP_FOR_WORK => array('L4', 'L6', 'L9'),
    );
  }

  public static function getDocsMap() {
    return array(
      self::DOC_IDENTITY => pht('KTP'),
      self::DOC_FAMILY => pht('KK'),
      self::DOC_SKCK => pht('SKCK'),
      self::DOC_TAX => pht('NPWP'),
      self::DOC_DOMICILE => pht('Surat Domisili'),
      self::DOC_CERTIFICATE => pht('Ijazah Terakhir'),
      self::DOC_OTHER => pht('Dokumen Lainnya'),
      self::DOC_ADDITIONAL => pht('Tambahan dokumen'),
    );
  }

  public static function getCommitmentMap() {
    return array(
      3 => pht('3 Bulan'),
      4 => pht('4 Bulan'),
      5 => pht('5 Bulan'),
      6 => pht('6 Bulan'),
    );
  }

  protected function readField($field) {
    switch ($field) {
      // Make sure these return booleans.
      case 'isRsp':
        return (bool)$this->isRsp;
      case 'isEligibleForJob':
        return (bool)$this->isEligibleForJob;
      default:
        return parent::readField($field);
    }
  }

  public static function initializeNewProfile(PhabricatorUser $actor) {

    $app = id(new PhabricatorApplicationQuery())
      ->setViewer($actor)
      ->withClasses(array('PhabricatorSuiteApplication'))
      ->executeOne();

    $view_policy = $app->getPolicy(
      PhabricatorSuiteCapabilityManageBilling::CAPABILITY);
    $edit_policy = $app->getPolicy(
      PhabricatorSuiteCapabilityManageBilling::CAPABILITY);

    return id(new self())
      ->setOwnerPHID($actor->getPHID())
      ->setIsRsp(0)
      ->setIsEligibleForJob(0)
      ->setGraduationTargetMonth(0)
      ->setUpFor('undefined')
      ->setViewPolicy($view_policy)
      ->setEditPolicy($edit_policy);
  }

  public static function createNewProfile(
    PhabricatorUser $actor,
    PhabricatorContentSource $content_source) {

    $profile = self::initializeNewProfile($actor);

    $xactions = array();
    $xactions[] = id(new SuiteProfileTransaction())
      ->setTransactionType(SuiteProfileUpForTransaction::TRANSACTIONTYPE)
      ->setNewValue('undefined');

    $xactions[] = id(new SuiteProfileTransaction())
      ->setTransactionType(
        SuiteProfileIsRspTransaction::TRANSACTIONTYPE)
      ->setNewValue(true);

    $xactions[] = id(new SuiteProfileTransaction())
      ->setTransactionType(
        SuiteProfileGraduationTargetMonthTransaction::TRANSACTIONTYPE)
      ->setNewValue(0);

    $editor = id(new SuiteProfileEditor())
      ->setActor($actor)
      ->setContentSource($content_source)
      ->setContinueOnNoEffect(true);

    // We create an profile for you the first time you visit Suite.
    $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();

      $editor->applyTransactions($profile, $xactions);

      $profile->setIsRsp(0);
      $profile->save();

    unset($unguarded);

    return $profile;
  }

  protected function getConfiguration() {
    return array(
      self::CONFIG_AUX_PHID => true,
      self::CONFIG_COLUMN_SCHEMA => array(
        'mailKey' => 'bytes20',
        'isRsp' => 'bool',
        'isEligibleForJob' => 'bool',
        'graduationTargetMonth' => 'uint32',
        'upFor' => 'text64',
        'identityDocPHID' => 'phid?',
        'taxDocPHID' => 'phid?',
        'skckDocPHID' => 'phid?',
        'familyDocPHID' => 'phid?',
        'domicileDocPHID' => 'phid?',
        'certificateDocPHID' => 'phid?',
        'otherDocPHID' => 'phid?',
        'additionalDocPHID' => 'phid?',
      ),
      self::CONFIG_SERIALIZATION => array(
        'cv' => self::SERIALIZATION_JSON,
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
      SuiteProfilePHIDType::TYPECONST);
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

  public function loadSignedLegalpadMonograms() {
    $user = $this->loadUser();

    $signatures = id(new LegalpadDocumentSignatureQuery())
                  ->setViewer(PhabricatorUser::getOmnipotentUser())
                  ->withSignerPHIDs(array($user->getPHID()))
                  ->execute();
    $phids = mpull($signatures, null, 'getDocumentPHID');

    if ($phids) {
      $docs = id(new LegalpadDocumentQuery())
              ->setViewer(PhabricatorUser::getOmnipotentUser())
              ->withPHIDs(array_keys($phids))
              ->execute();
      $monograms = mpull($docs, null, 'getMonogram');
      return array_keys($monograms);
    }

    return array();
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
    return '/suite/profile/view/'.$this->getID().'/';
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
        return pht('Owners of balance can always view it.');
      case PhabricatorPolicyCapability::CAN_EDIT:
        return pht('Owners of balance can always edit it.');
    }
    return null;
  }

  public function getApplicationTransactionEditor() {
    return new SuiteProfileEditor();
  }

  public function getApplicationTransactionTemplate() {
    return new SuiteProfileTransaction();
  }

  public static function sendTeachableCredsEmail(
    PhabricatorUser $user,
    $password) {
    $real_name = $user->getRealName();
    $email =  $user->loadPrimaryEmail()->getAddress();

    $link = 'https://course.refactory.id';

    $body = sprintf(
      "%s\n\n%s\n\n  %s\n\n%s",
      pht('Hi %s', $real_name),
      pht(
        'This is your account for accessing %s',
        $link),
      pht('Email: %s', $email),
      pht('Password: %s', $password));

    id(new PhabricatorMetaMTAMail())
      ->addRawTos(array($email))
      ->setForceDelivery(true)
      ->setSubject(pht('[Suite] Refactory Course'))
      ->setBody($body)
      ->setRelatedPHID($user->getPHID())
      ->saveAndSend();
  }

/* -(  PhabricatorCustomFieldInterface  )------------------------------------ */


  public function getCustomFieldSpecificationForRole($role) {
    return PhabricatorEnv::getEnvConfig('suite-profile.fields');
  }

  public function getCustomFieldBaseClass() {
    return 'SuiteProfileCustomField';
  }

  public function getCustomFields() {
    return $this->assertAttached($this->customFields);
  }

  public function attachCustomFields(PhabricatorCustomFieldAttachment $fields) {
    $this->customFields = $fields;
    return $this;
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
        ->setDescription(pht('User PHID of the balance owner.')),
    );
  }

  public function getFieldValuesForConduit() {
    return array(
      'ownerPHID' => $this->getOwnerPHID(),
      'isRsp' => $this->getIsRsp(),
      'upFor' => $this->getUpFor(),
    );
  }

  public function getConduitSearchAttachments() {
    return array();
  }
}
