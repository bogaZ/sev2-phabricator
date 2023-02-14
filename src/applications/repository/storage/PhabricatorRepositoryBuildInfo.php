<?php

final class PhabricatorRepositoryBuildInfo
  extends PhabricatorRepositoryDAO
  implements
  PhabricatorPolicyInterface {

  protected $repositoryPHID;
  protected $configuration;
  protected $viewPolicy;
  protected $editPolicy;

  public static function initializeNewBuildInfo(PhabricatorUser $actor) {
    $app = id(new PhabricatorApplicationQuery())
      ->setViewer($actor)
      ->withClasses(array('PhabricatorDiffusionApplication'))
      ->executeOne();

    $view_policy = $app->getPolicy(DiffusionDefaultViewCapability::CAPABILITY);
    $edit_policy = $app->getPolicy(DiffusionDefaultEditCapability::CAPABILITY);

    return id(new self())
      ->setViewPolicy($view_policy)
      ->setEditPolicy($edit_policy);
  }

  protected function getConfiguration() {
    return array(
      self::CONFIG_AUX_PHID => true,
      self::CONFIG_COLUMN_SCHEMA => array(
        'repositoryPHID' => 'phid',
        'configuration' => 'text?',
      ),
      self::CONFIG_KEY_SCHEMA => array(
        'key_repository' => array(
          'columns' => array('repositoryPHID'),
        ),
      ),
    ) + parent::getConfiguration();
  }

  public function generatePHID() {
    return PhabricatorPHID::generateNewPHID(
      PhabricatorRepositoryBuildInfoPHIDType::TYPECONST);
  }

  public function getApplicationTransactionEditor() {
    return new DiffusionRepositoryBuildInfoEditor();
  }

  public function getApplicationTransactionTemplate() {
    return new PhabricatorRepositoryBuildInfoTransaction();
  }

/* -(  PhabricatorPolicyInterface  )---------------------------------------- */

  public function getCapabilities() {
    return array(
      PhabricatorPolicyCapability::CAN_VIEW,
      PhabricatorPolicyCapability::CAN_EDIT,
      DiffusionPushCapability::CAPABILITY,
    );
  }

  public function getPolicy($capability) {
    switch ($capability) {
      case PhabricatorPolicyCapability::CAN_VIEW:
        return $this->getViewPolicy();
      case PhabricatorPolicyCapability::CAN_EDIT:
        return $this->getEditPolicy();
      case DiffusionPushCapability::CAPABILITY:
        return $this->getPushPolicy();
    }
  }

  public function hasAutomaticCapability($capability, PhabricatorUser $user) {
    return false;
  }

}
