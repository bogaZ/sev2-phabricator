<?php

final class JobPostingApplicant extends JobDAO
  implements
    PhabricatorDestructibleInterface,
    PhabricatorPolicyInterface {

  protected $postingPHID;
  protected $applicantPHID;
  protected $inviterPHID;
  protected $status;
  protected $availability;

  const STATUS_INVITED = 'invited';
  const STATUS_APPLIED = 'applied';
  const STATUS_DECLINED = 'declined';
  const STATUS_UNINVITED = 'uninvited';

  const AVAILABILITY_DEFAULT = 'default';
  const AVAILABILITY_AVAILABLE = 'available';
  const AVAILABILITY_BUSY = 'busy';
  const AVAILABILITY_AWAY = 'away';

  private $job = self::ATTACHABLE;

  public static function initializeNewApplicant(
    PhabricatorUser $actor,
    JobPosting $job,
    $applicant_phid) {
      return id(new self())
        ->setPostingPHID($job->getPHID())
        ->setApplicantPHID($applicant_phid)
        ->setInviterPHID($actor->getPHID())
        ->setStatus('active')
        ->setAvailability('open')
        ->attachJob($job);
    }

  protected function getConfiguration() {
    return array(
      self::CONFIG_COLUMN_SCHEMA => array(
        'status' => 'text64',
        'availability' => 'text64',
      ),
      self::CONFIG_KEY_SCHEMA => array(
        'key_posting' => array(
          'columns' => array('postingPHID', 'applicantPHID'),
          'unique' => true,
        ),
        'key_applicant' => array(
          'columns' => array('applicantPHID'),
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
    return $this->getJob()->getPolicy($capability);
  }

  public function hasAutomaticCapability($capability, PhabricatorUser $viewer) {
    return false;
  }
}
