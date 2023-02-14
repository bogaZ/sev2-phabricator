<?php

final class CoursepathItemTestSubmission extends CoursepathDAO
  implements
    PhabricatorDestructibleInterface,
    PhabricatorPolicyInterface,
    PhabricatorSubscribableInterface,
    PhabricatorNgramsInterface {

  protected $testPHID;
  protected $answer;
  protected $score;
  protected $session;
  protected $creatorPHID;
  protected $editPolicy;

  private $test = self::ATTACHABLE;

  public static function initializeNewSubmission(
    PhabricatorUser $actor,
    $user_phid,
    $test_phid,
    $answer,
    $score,
    $session) {
    $app = id(new PhabricatorApplicationQuery())
      ->setViewer($actor)
      ->withClasses(array('PhabricatorCoursepathApplication'))
      ->executeOne();

    $edit_policy = $app->getPolicy(CoursepathDefaultEditCapability::CAPABILITY);
    return id(new self())
      ->setCreatorPHID($user_phid)
      ->setTestPHID($test_phid)
      ->setAnswer($answer)
      ->setScore($score)
      ->setSession($session)
      ->setEditPolicy($edit_policy);
  }

  protected function getConfiguration() {
    return array(
      self::CONFIG_AUX_PHID => true,
      self::CONFIG_COLUMN_SCHEMA => array(
        'answer' => 'text?',
        'score' => 'uint32?',
        'session' => 'uint32?',
      ),
      self::CONFIG_KEY_SCHEMA => array(
        'key_creator' => array(
          'columns' => array('creatorPHID', 'dateModified'),
        ),
        'key_test' => array(
          'columns' => array('testPHID'),
        ),
        'key_itemtest' => array(
            'columns' => array('phid', 'testPHID'),
        ),
      ),
    ) + parent::getConfiguration();
  }

  public function generatePHID() {
    return PhabricatorPHID::generateNewPHID(
      CoursepathItemTestSubmissionPHIDType::TYPECONST);
  }

  public function hasTest() {
    return $this->test != self::ATTACHABLE;
  }

  public function attachTest(CoursepathItemTest $test) {
    $this->test = $test;
    return $this;
  }

  public function getTest() {
    return $this->assertAttached($this->test);
  }

  public function getApplicationTransactionEditor() {
    return new PhabricatorCoursepathItemSubmissionEditor();
  }

  public function getApplicationTransactionTemplate() {
    return new CoursepathItemTestSubmissionTransaction();
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

/* -(  PhabricatorNgramInterface  )------------------------------------------ */


  public function newNgrams() {
    return array(
      id(new CoursepathItemNameNgrams())
        ->setValue($this->getName()),
    );
  }

/* -(  PhabricatorSubscribableInterface  )----------------------------------- */

  public function isAutomaticallySubscribed($phid) {
    return false;
  }

}
