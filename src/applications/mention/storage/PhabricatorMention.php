<?php

final class PhabricatorMention extends PhabricatorMentionDAO
  implements
  PhabricatorPolicyInterface,
  PhabricatorConduitResultInterface {

  protected $callerPHID;
  protected $objectPHID;
  protected $message;

  public static function initializeNewItem(PhabricatorUser $author) {
    $app = id(new PhabricatorApplicationQuery())
      ->setViewer($author)
      ->withClasses(array('PhabricatorMentionApplication'))
      ->executeOne();

    $view_policy = $app->getPolicy(
      MentionCapability::CAPABILITY);
    $edit_policy = $app->getPolicy(
      MentionCapability::CAPABILITY);

    return id(new self())
      ->setOwnerPHID($author->getPHID())
      ->setSeenPHIDs(array($author->getPHID()))
      ->setViewPolicy($view_policy)
      ->setEditPolicy($edit_policy);
  }

  protected function getConfiguration() {
    return array(
        self::CONFIG_AUX_PHID => true,
        self::CONFIG_COLUMN_SCHEMA => array(
          'callerPHID' => 'phid',
          'objectPHID' => 'phid',
          'message' => 'text?',
        ),
      ) + parent::getConfiguration();
  }

  public function getPHIDType() {
    return PhabricatorMentionPHIDType::TYPECONST;
  }

  public function generatePHID() {
    return PhabricatorPHID::generateNewPHID(
      PhabricatorMentionPHIDType::TYPECONST);
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
        ->setKey('callerPHID') // caller
        ->setType('phid')
        ->setDescription(pht('The userPHID for the mention.')),
      id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('objectPHID') // either room, ticket or event
        ->setType('list<phid>')
        ->setDescription(pht('The place user')),
      id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('message')
        ->setType('remarkup')
        ->setDescription(pht('The message of the mention.')),
    );
  }

  private function findUser(string $phid) {
    return id(new PhabricatorPeopleQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->needProfileImage(true)
      ->withPHIDs(array($phid))
      ->executeOne();
  }

  private function setUserResponse($user) {
    if (!is_null($user)) {
      return [
        'userPHID' => $user->getPHID(),
        'userName' => $user->getUserName(),
        'realName' => $user->getRealName(),
        'profileImageURI' => $user->getProfileImageURI(),
      ];
    }
    return [];
  }

  public function getFieldValuesForConduit() {
    $mentioned_phids = id(new PhabricatorMentionMentionedQuery())
      ->withID($this->id)
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->execute();

    $mentioned_users = array();
    if (count($mentioned_phids)) {
      foreach ($mentioned_phids as $mentioned) {
        $user = $this->findUser($mentioned->getUserPHID());
        $user_data = $this->setUserResponse($user);
        array_push($mentioned_users, $user_data);
      }
    }

    $object = id(new PhabricatorObjectQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withPHIDs(array($this->getObjectPHID()))
      ->executeOne();

    $object_data = array();
    if (!is_null($object)) {
      $object_data = [
        'id' => $object->getID(),
        'title' => phid_get_type($object->getPHID()) === 'CEVT'
          ? $object->getName()
          : $object->getTitle(),
        'phid' => $object->getPhid(),
      ];
    }

    $engine = PhabricatorMarkupEngine::getEngine()
      ->setConfig('viewer', PhabricatorUser::getOmnipotentUser());
    $parsed_comment = $engine->markupText($this->getMessage());
    if ($parsed_comment instanceof PhutilSafeHTML) {
      $parsed_comment = $parsed_comment->getHTMLContent();
    }

    $message_data = [
      'text' => $this->getMessage(),
      'html' => $parsed_comment,
    ];

    $creator = $this->findUser($this->getCallerPHID());

    return array(
      'message' => $message_data,
      'creator' => $this->setUserResponse($creator),
      'mentionedUsers' => $mentioned_users,
      'object' => $object_data,
    );
  }

  public function getConduitSearchAttachments() {
    return array();
  }
}
