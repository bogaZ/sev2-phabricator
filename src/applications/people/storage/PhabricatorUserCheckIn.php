<?php

final class PhabricatorUserCheckIn extends PhabricatorUserDAO
  implements
    PhabricatorPolicyInterface,
    PhabricatorConduitResultInterface {


  protected $phid;
  protected $viewPolicy;
  protected $editPolicy;


  protected function getConfiguration() {
    return array(
      self::CONFIG_AUX_PHID => true,
      self::CONFIG_COLUMN_SCHEMA => array(
        'phid' => 'phid',
        'dateCreated' => 'epoch',
        'dateModified' => 'epoch',
      ),
      self::CONFIG_KEY_SCHEMA => array(
        'key_dateCreated' => array(
          'columns' => array('dateCreated'),
        ),
      ),
    ) + parent::getConfiguration();
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

  public function hasAutomaticCapability($capability, PhabricatorUser $user) {
    return ($user->getPHID() == $this->getPHID());
  }

  public function describeAutomaticCapability($capability) {
    return pht('The author of a paste can always view and edit it.');
  }

/* -(  PhabricatorConduitResultInterface  )---------------------------------- */


  public function getFieldSpecificationsForConduit() {
    return array(
        id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('phid')
        ->setType('phid')
        ->setDescription(pht('User PHID')),
    );
  }

  public function getFieldValuesForConduit() {
    return array(
      'phid' => $this->getPHID(),
    );
  }

  public function getConduitSearchAttachments() {
    return array(
    );
  }



}
