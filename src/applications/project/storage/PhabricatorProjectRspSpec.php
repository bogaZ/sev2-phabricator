<?php

final class PhabricatorProjectRspSpec extends PhabricatorProjectDAO
  implements
    PhabricatorDestructibleInterface,
    PhabricatorPolicyInterface {

  protected $authorPHID;
  protected $projectPHID;
  protected $coursepathItemPHID;
  protected $billingUserPHID;
  protected $status;
  protected $storyPointCurrency;
  protected $storyPointValue;
  protected $storyPointBilledValue;
  protected $stack;

  protected $viewPolicy;
  protected $editPolicy;

  const CURRENCY_IDR = 'IDR';
  const CURRENCY_USD = 'USD';

  const STATUS_OPEN = 'open';
  const STATUS_CLOSED = 'closed';
  const STATUS_SUSPENDED = 'suspended';

  private $author = self::ATTACHABLE;
  private $project = self::ATTACHABLE;
  private $coursepathItem = self::ATTACHABLE;
  private $billingUser = self::ATTACHABLE;

  public static function initializeNewRspSpec(
    PhabricatorUser $actor,
    PhabricatorProject $project) {
      $app = id(new PhabricatorApplicationQuery())
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->withClasses(array('PhabricatorProjectApplication'))
        ->executeOne();

      $view_policy = $app->getPolicy(
        ProjectDefaultViewCapability::CAPABILITY);
      $edit_policy = $app->getPolicy(
        ProjectDefaultEditCapability::CAPABILITY);

      return id(new self())
        ->setProjectPHID($project->getPHID())
        ->setAuthorPHID($actor->getPHID())
        ->setStatus(self::STATUS_OPEN)
        ->setViewPolicy($view_policy)
        ->setEditPolicy($edit_policy)
        ->attachAuthor($actor)
        ->attachProject($project);
    }

  protected function getConfiguration() {
    return array(
      self::CONFIG_COLUMN_SCHEMA => array(
        'authorPHID' => 'phid',
        'coursepathItemPHID' => 'phid',
        'billingUserPHID' => 'phid',
        'storyPointValue' => 'uint32?',
        'storyPointBilledValue' => 'uint32?',
        'storyPointCurrency' => 'text32',
        'status' => 'text64',
        'availability' => 'text64',
        'stack' => 'text32?',
      ),
      self::CONFIG_KEY_SCHEMA => array(
        'key_path' => array(
          'columns' => array('projectPHID', 'coursepathItemPHID'),
          'unique' => true,
        ),
      ),
    ) + parent::getConfiguration();
  }

  public static function getCurrencyMap() {
    return array(
      self::CURRENCY_IDR => pht('Rupiah'),
      self::CURRENCY_USD => pht('Dollar'),
    );
  }

  public function attachProject(PhabricatorProject $project) {
    $this->project = $project;
    return $this;
  }

  public function getProject() {
    return $this->assertAttached($this->project);
  }

  public function attachAuthor(PhabricatorUser $author) {
    $this->author = $author;
    return $this;
  }

  public function getAuthor() {
    return $this->assertAttached($this->author);
  }

  public function attachCoursepathItem(CoursepathItem $item) {
    $this->coursepathItem = $item;
    return $this;
  }

  public function getCoursepathItem() {
    return $this->assertAttached($this->coursepathItem);
  }

  public function attachBillingUser(PhabricatorUser $user) {
    $this->billingUser = $user;
    return $this;
  }

  public function getBillingUser() {
    return $this->assertAttached($this->billingUser);
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
    switch ($capability) {
      case PhabricatorPolicyCapability::CAN_VIEW:
        return $this->getViewPolicy();
      case PhabricatorPolicyCapability::CAN_EDIT:
        return $this->getEditPolicy();
    }
  }

  public function hasAutomaticCapability($capability, PhabricatorUser $viewer) {
    return false;
  }
}
