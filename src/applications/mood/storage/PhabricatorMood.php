<?php

final class PhabricatorMood extends PhabricatorMoodDAO
  implements
    PhabricatorPolicyInterface,
    PhabricatorConduitResultInterface {

  protected $userPHID;
  protected $mood;
  protected $message;
  protected $isForDev;

  protected function getConfiguration() {
    return array(
      self::CONFIG_AUX_PHID => true,
      self::CONFIG_COLUMN_SCHEMA => array(
        'mood' => 'text',
        'message' => 'text?',
        'isForDev' => 'bool',
      ),
    ) + parent::getConfiguration();
  }

  public function getPHIDType() {
    return PhabricatorMoodPHIDType::TYPECONST;
  }

  public function generatePHID() {
    return PhabricatorPHID::generateNewPHID(
      PhabricatorMoodPHIDType::TYPECONST);
  }

/* -(  PhabricatorPolicyInterface  )----------------------------------------- */


  public function getCapabilities() {
    return array(
      PhabricatorPolicyCapability::CAN_VIEW,
    );
  }

  public function getPolicy($capability) {
    return PhabricatorPolicies::getMostOpenPolicy();
  }

  public function hasAutomaticCapability($capability, PhabricatorUser $viewer) {
    return false;
  }

/* -(  PhabricatorConduitResultInterface  )---------------------------------- */

  public function getFieldSpecificationsForConduit() {
    return array(
      id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('userPHIDs')
        ->setType('list<phid>')
        ->setDescription(pht('The userPHID for the mood.')),
      id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('mood')
        ->setType('string')
        ->setDescription(pht('The mood of the user.')),
      id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('message')
        ->setType('remarkup')
        ->setDescription(pht('The message of the mood.')),
      id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('startDate')
        ->setType('epoch')
        ->setDescription(pht('The start date of the mood.')),
      id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('endDate')
        ->setType('epoch')
        ->setDescription(pht('The end date of the mood.')),
      id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('isForDev')
        ->setType('bool')
        ->setDescription(pht('Development testing flag')),
    );
  }

  public function getFieldValuesForConduit() {
    return array(
      'userPHID' => $this->getUserPHID(),
      'mood' => $this->getMood(),
      'message' => $this->getMessage(),
      'isForDev' => (int)$this->getIsForDev(),
    );
  }

  public function setIsForDev($is_for_dev) {
    if ($is_for_dev) {
      $this->isForDev = 1;
    } else {
      $this->isForDev = 0;
    }
    return $this;
  }

  public function getConduitSearchAttachments() {
    return array(
      id(new MoodUserSearchEngineAttachment())
      ->setAttachmentKey('user'),
    );
  }
}
