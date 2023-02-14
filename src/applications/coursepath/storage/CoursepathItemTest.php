<?php

final class CoursepathItemTest extends CoursepathDAO
  implements
    PhabricatorApplicationTransactionInterface,
    PhabricatorDestructibleInterface,
    PhabricatorSubscribableInterface,
    PhabricatorPolicyInterface {

  const TYPE_DAILY = 'daily';
  const TYPE_QUIZ = 'quiz';
  const TYPE_EXERCISE = 'exercise';

  const SEVERITY_BASIC = 'basic';
  const SEVERITY_MEDIUM = 'medium';
  const SEVERITY_INTERMEDIATE = 'intermediate';

  const SUITE_WPM = 'wpm';

  const CONSTRAINT_MAX_WPM = 100;
  const CONSTRAINT_MAX_BASIC = 100;
  const CONSTRAINT_MAX_INTERMEDIATE = 100;
  const CONSTRAINT_MAX_STACKOVERFLOW = 100;

  const CONSTRAINT_MIN_WPM = 50;
  const CONSTRAINT_MIN_BASIC = 90;
  const CONSTRAINT_MIN_INTERMEDIATE = 85;
  const CONSTRAINT_MIN_STACKOVERFLOW = 100;

  protected $creatorPHID;
  protected $itemPHID;
  protected $title;
  protected $question;
  protected $answer;
  protected $type;
  protected $severity;
  protected $status;
  protected $editPolicy;
  protected $testCode;
  protected $stack;
  protected $suiteType;
  protected $isNotAutomaticallyGraded;

  private $item = self::ATTACHABLE;
  private $options = self::ATTACHABLE;
  private $submissions = self::ATTACHABLE;

  public static function getTypeMap() {
    return array(
      self::TYPE_DAILY => pht('Daily'),
      self::TYPE_QUIZ => pht('Quiz'),
      self::TYPE_EXERCISE => pht('Exercise'),
    );
  }

  public static function getSeverityMap() {
    return array(
      self::SEVERITY_BASIC => pht('Basic'),
      self::SEVERITY_MEDIUM => pht('Medium'),
      self::SEVERITY_INTERMEDIATE => pht('Intermediate'),
    );
  }

  public static function initializeNewTest(
    PhabricatorUser $actor,
    $item_phid) {
    $app = id(new PhabricatorApplicationQuery())
      ->setViewer($actor)
      ->withClasses(array('PhabricatorCoursepathApplication'))
      ->executeOne();

    $edit_policy = $app->getPolicy(CoursepathDefaultEditCapability::CAPABILITY);
    return id(new self())
      ->setCreatorPHID($actor->getPHID())
      ->setItemPHID($item_phid)
      ->setStatus('active')
      ->setEditPolicy($edit_policy);
  }

  protected function getConfiguration() {
    return array(
      self::CONFIG_AUX_PHID => true,
      self::CONFIG_COLUMN_SCHEMA => array(
        'title' => 'sort255',
        'question' => 'text?',
        'answer' => 'text?',
        'type' => 'text32',
        'severity' => 'text32',
        'status' => 'text32',
        'testCode' => 'text32?',
        'stack' => 'text32?',
        'isNotAutomaticallyGraded' => 'bool',
        'suiteType' => 'text32?',
      ),
      self::CONFIG_KEY_SCHEMA => array(
        'key_creator' => array(
          'columns' => array('creatorPHID', 'dateModified'),
        ),
        'key_item' => array(
          'columns' => array('itemPHID'),
        ),
      ),
    ) + parent::getConfiguration();
  }

  public function generatePHID() {
    return PhabricatorPHID::generateNewPHID(
      CoursepathItemTestPHIDType::TYPECONST);
  }

  public function attachItem(CoursepathItem $item) {
    $this->item = $item;
    return $this;
  }

  public function getItem() {
    return $this->assertAttached($this->item);
  }

  public function getOptions() {
    return $this->assertAttached($this->options);
  }

  public function attachOptions(array $options) {
    assert_instances_of($options, 'CoursepathItemTestOption');
    $this->options = $options;
    return $this;
  }

  public function getSubmissions() {
    return $this->assertAttached($this->submissions);
  }

  public function attachSubmissions(array $submissions) {
    assert_instances_of($submissions, 'CoursepathItemTestSubmission');
    $this->submissions = $submissions;
    return $this;
  }

  public function getAvailableAnswerOptions() {
    return array(
      null => 'No Correct Answer',
      'A' => 'A',
      'B' => 'B',
      'C' => 'C',
      'D' => 'D',
      'E' => 'E',
      'F' => 'F',
    );
  }

  public function getApplicationTransactionEditor() {
    return new PhabricatorCoursepathItemTestEditor();
  }

  public function getApplicationTransactionTemplate() {
    return new CoursepathItemTestTransaction();
  }

  public function getViewURI($item_id, $test_id) {
    $test_id = $this->getID();
    $uri = "/coursepath/item/view/$item_id";
    $view_uri = "$uri/tests/view/$test_id/";
    return id(new PhutilURI($view_uri));
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
