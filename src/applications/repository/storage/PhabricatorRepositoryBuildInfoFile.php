<?php

final class PhabricatorRepositoryBuildInfoFile
  extends PhabricatorRepositoryDAO
  implements
  PhabricatorPolicyInterface {

  protected $buildPHID;
  protected $filePHID;
  protected $filename;
  protected $viewPolicy;
  protected $editPolicy;

  const FILENAME_MYSQL = 'mysql';
  const FILENAME_DOCKER_COMPOSE = 'docker_compose';

  public static function initializeNewBuildInfoFile(PhabricatorUser $actor) {
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
      self::CONFIG_COLUMN_SCHEMA => array(
        'buildPHID' => 'phid',
        'filePHID' => 'phid?',
        'filename' => 'text255?',
      ),
      self::CONFIG_KEY_SCHEMA => array(
        'key_build' => array(
          'columns' => array('buildPHID'),
        ),
      ),
    ) + parent::getConfiguration();
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
