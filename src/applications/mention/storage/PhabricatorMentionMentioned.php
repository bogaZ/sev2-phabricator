<?php

final class PhabricatorMentionMentioned extends PhabricatorMentionDAO
  implements
  PhabricatorPolicyInterface {

  protected $mentionID;
  protected $userPHID;

  protected function getConfiguration() {
    return array(
        self::CONFIG_COLUMN_SCHEMA => array(
          'mentionID' => 'id',
          'userPHID' => 'phid',
        ),
      ) + parent::getConfiguration();
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
        ->setKey('mentionID') // caller
        ->setType('int10')
        ->setDescription(pht('The userPHID for the mention.')),
      id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('userPHID') // caller
        ->setType('phid')
        ->setDescription(pht('The userPHID for the mention.')),
    );
  }

  public function getConduitSearchAttachments() {
    return array();
  }
}
