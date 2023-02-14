<?php

final class JobPostingTechStack extends JobDAO
  implements
    PhabricatorDestructibleInterface,
    PhabricatorPolicyInterface {

  protected $authorPHID;
  protected $postingPHID;
  protected $coursepathItemPHID;
  protected $status;

  protected $viewPolicy;
  protected $editPolicy;

  const STATUS_OPEN = 'open';
  const STATUS_CLOSED = 'closed';
  const STATUS_SUSPENDED = 'suspended';

  private $author = self::ATTACHABLE;
  private $job = self::ATTACHABLE;
  private $coursepathItem = self::ATTACHABLE;

  public static function initializeNewRspSpec(
    PhabricatorUser $actor,
    JobPosting $job) {
      $app = id(new PhabricatorApplicationQuery())
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->withClasses(array('PhabricatorJobApplication'))
        ->executeOne();

      $view_policy = $app->getPolicy(
        JobDefaultViewCapability::CAPABILITY);
      $edit_policy = $app->getPolicy(
        JobDefaultEditCapability::CAPABILITY);

      return id(new self())
        ->setPostingPHID($job->getPHID())
        ->setAuthorPHID($actor->getPHID())
        ->setStatus(self::STATUS_OPEN)
        ->setViewPolicy($view_policy)
        ->setEditPolicy($edit_policy)
        ->attachAuthor($actor)
        ->attachJob($job);
    }

  protected function getConfiguration() {
    return array(
      self::CONFIG_COLUMN_SCHEMA => array(
        'authorPHID' => 'phid',
        'coursepathItemPHID' => 'phid',
        'status' => 'text64',
        'availability' => 'text64',
      ),
      self::CONFIG_KEY_SCHEMA => array(
        'key_path' => array(
          'columns' => array('postingPHID', 'coursepathItemPHID'),
          'unique' => true,
        ),
      ),
    ) + parent::getConfiguration();
  }

  public function attachJob(JobPosting $job) {
    $this->job = $job;
    return $this;
  }

  public function getJob() {
    return $this->assertAttached($this->job);
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
