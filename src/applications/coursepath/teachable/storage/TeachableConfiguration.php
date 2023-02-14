<?php

final class TeachableConfiguration extends CoursepathDAO
  implements
    PhabricatorApplicationTransactionInterface,
    PhabricatorDestructibleInterface,
    PhabricatorSubscribableInterface,
    PhabricatorPolicyInterface {

  protected $creatorPHID;
  protected $email;
  protected $url;
  protected $password;
  protected $editPolicy;

  public static function initializeNewConfig(
    PhabricatorUser $actor) {
    $app = id(new PhabricatorApplicationQuery())
      ->setViewer($actor)
      ->withClasses(array('PhabricatorCoursepathApplication'))
      ->executeOne();

    $edit_policy = $app->getPolicy(CoursepathDefaultEditCapability::CAPABILITY);
    return id(new self())
      ->setCreatorPHID($actor->getPHID())
      ->setEditPolicy($edit_policy);
  }

  protected function getConfiguration() {
    return array(
      self::CONFIG_AUX_PHID => true,
      self::CONFIG_COLUMN_SCHEMA => array(
        'url' => 'sort255',
        'email' => 'sort255',
        'password' => 'sort255',
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
        TeachableConfigurationPHIDType::TYPECONST);
  }


  public function getApplicationTransactionEditor() {
    return new PhabricatorTeachableEditor();
  }

  public function getApplicationTransactionTemplate() {
    return new TeachableTransaction();
  }

  public function getViewURI($item_id) {
    $test_id = $this->getID();
    $view_uri = "/coursepath/item/view/$item_id/stacks/view/$test_id/";
    return id(new PhutilURI($view_uri));
  }

  protected function getPrimaryTableAlias() {
    return 'coursepath_teachable';
  }

  public function getTableName() {
    return sev2table('coursepath_teachable');
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
