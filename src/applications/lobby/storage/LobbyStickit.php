<?php

final class LobbyStickit
  extends LobbyDAO
  implements
    PhabricatorPolicyInterface,
    PhabricatorDestructibleInterface,
    PhabricatorApplicationTransactionInterface,
    PhabricatorTokenReceiverInterface,
    PhabricatorConduitResultInterface {

  protected $ownerPHID;
  protected $noteType;
  protected $title;
  protected $content;
  protected $message;
  protected $viewPolicy;
  protected $editPolicy;
  protected $isArchived;
  protected $description;
  protected $progress;
  protected $seenPHIDs = array();

  private $owner = null;
  private $contributors = self::ATTACHABLE;
  private $taskPHIDs = self::ATTACHABLE;

  const TYPE_MEMO = 'memo';
  const TYPE_PITCH = 'pitch';
  const TYPE_PRAISE = 'praise';
  const TYPE_MOM = 'mom';

  public static function getTypeMap() {
    return array(
      self::TYPE_MEMO => pht('Announcement'),
      self::TYPE_PITCH => pht('Pitch an Idea'),
      self::TYPE_PRAISE => pht('Praise'),
      self::TYPE_MOM => pht('Minutes of Meeting'),
    );
  }

  public static function initializeNewItem(PhabricatorUser $author) {
    $app = id(new PhabricatorApplicationQuery())
      ->setViewer($author)
      ->withClasses(array('PhabricatorLobbyApplication'))
      ->executeOne();

    $view_policy = $app->getPolicy(
      LobbyJoinCapability::CAPABILITY);
    $edit_policy = $app->getPolicy(
      LobbyJoinCapability::CAPABILITY);

    return id(new self())
      ->setOwnerPHID($author->getPHID())
      ->setSeenPHIDs(array($author->getPHID()))
      ->setIsArchived(0)
      ->setProgress(0)
      ->setMessage('')
      ->setViewPolicy($view_policy)
      ->setEditPolicy($edit_policy);
  }

  public function getNoteTypeColor() {
    switch ($this->getNoteType()) {
      case self::TYPE_MEMO:
        return PHUITagView::COLOR_BLUE;
        break;

      case self::TYPE_PITCH:
        return PHUITagView::COLOR_GREEN;
        break;

      case self::TYPE_PRAISE:
        return PHUITagView::COLOR_PINK;
        break;

      case self::TYPE_MOM:
        return PHUITagView::COLOR_VIOLET;
        break;

      default:
        return PHUITagView::COLOR_GREY;
        break;
    }
  }

  public function getMailKey() {
    return;
  }

  public function setMailKey() {
    return;
  }

  protected function getConfiguration() {
    return array(
      self::CONFIG_AUX_PHID => true,
      self::CONFIG_SERIALIZATION => array(
        'seenPHIDs' => self::SERIALIZATION_JSON,
      ),
      self::CONFIG_COLUMN_SCHEMA => array(
        'title' => 'text255',
        'noteType' => 'text64?',
        'progress' => 'uint32',
        'content' => 'text',
        'message' => 'text?',
        'description' => 'text?',
        'isArchived' => 'bool?',
      ),
      self::CONFIG_KEY_SCHEMA => array(
        'key_type' => array(
          'columns' => array('noteType'),
        ),
      ),
    ) + parent::getConfiguration();
  }

  public function generatePHID() {
    return PhabricatorPHID::generateNewPHID(
      LobbyStickitPHIDType::TYPECONST);
  }

  public function getOwner() {
    return $this->assertAttached($this->owner);
  }

  public function attachTaskPHIDs(array $phids) {
    $this->getTaskPHIDs = $phids;
    return $this;
  }

  public function getTaskPHIDs() {
    return $this->assertAttached($this->getTaskPHIDs);
  }

  public function attachOwner(PhabricatorUser $owner) {
    $this->owner = $owner;
    return $this;
  }

  public function loadUser() {
    if ($this->owner) {
      return $this->owner;
    }

    $this->owner = id(new PhabricatorPeopleQuery())
                    ->setViewer(PhabricatorUser::getOmnipotentUser())
                    ->withPHIDs(array($this->getOwnerPHID()))
                    ->needProfile(true)
                    ->needProfileImage(true)
                    ->executeOne();

    return $this->owner;
  }

  public function getSeenUsers() {
    return $this->assertAttached($this->seenUsers);
  }

  public function attachSeenUsers(array $users) {
    $this->seenUsers = $users;
    return $this;
  }

  public function getViewURI() {
    if ($this->getNoteType() === 'goals') {
      return '/lobby/goals/'.$this->getID().'/';
    }
    return '/lobby/stickit/'.$this->getID().'/';
  }

  public function getEditURI() {
    return '/lobby/stickit/edit/'.$this->getID().'/';
  }

  public function save() {
    if (!$this->getMailKey()) {
      $this->setMailKey(Filesystem::readRandomCharacters(20));
    }

    $new_file =  parent::save();

    $story_type = 'PhabricatorLobbyStickitEditFeedStory';
    $story_data = array(
      'authorPHID' => $new_file->getOwnerPHID(),
      'objectPHID' => $new_file->getPHID(),
    );

    $subscribed_phids = $new_file->getUsersToNotifyOfTokenGiven();
    $related_phids = $subscribed_phids;
    $related_phids[] = $new_file->getOwnerPHID();

    id(new PhabricatorFeedStoryPublisher())
      ->setStoryType($story_type)
      ->setStoryData($story_data)
      ->setStoryTime(time())
      ->setStoryAuthorPHID($new_file->getOwnerPHID())
      ->setRelatedPHIDs($related_phids)
      ->setPrimaryObjectPHID($new_file->getPHID())
      ->setSubscribedPHIDs($subscribed_phids)
      ->publish();
    return $new_file;
  }

  public function seenBy(PhabricatorUser $user) {
    $old_phids = $this->getSeenPHIDs();
    $new_phids = array_merge($old_phids, array($user->getPHID()));
    $this->setSeenPHIDs(array_unique($new_phids));

    $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();

      $this->save();

    unset($unguarded);
  }

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
    return $viewer->getPHID() == $this->ownerPHID;
  }

  public function describeAutomaticCapability($capability) {
    switch ($capability) {
      case PhabricatorPolicyCapability::CAN_VIEW:
        return pht('User can always view it.');
      case PhabricatorPolicyCapability::CAN_EDIT:
        return pht('User can always edit it.');
    }
    return null;
  }

  public function getApplicationTransactionEditor() {
    return new LobbyStickitEditor();
  }

  public function getApplicationTransactionTemplate() {
    return new LobbyStickitTransaction();
  }

/* -(  PhabricatorTokenReceiverInterface  )---------------------------------- */


public function getUsersToNotifyOfTokenGiven() {
  return array($this->getOwnerPHID());
}

/* -(  PhabricatorDestructibleInterface  )----------------------------------- */


  public function destroyObjectPermanently(
    PhabricatorDestructionEngine $engine) {

    $this->delete();
  }

/* -(  PhabricatorConduitResultInterface  )---------------------------------- */


  public function getFieldSpecificationsForConduit() {
    return array(
      id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('ownerPHID')
        ->setType('string')
        ->setDescription(pht('User PHID.')),
    );
  }

  public function getFieldValuesForConduit() {
    if ($this->getNoteType() === 'goals') {
      return $this->conduitGoals();
    } else {
      return $this->conduitStickit();
    }
  }

  public function conduitStickit() {
    $user = $this->loadUser();
    $seen_phids = $this->seenPHIDs;
    $seen_user = [];
    if ($seen_phids) {
      $seen_data = id(new PhabricatorPeopleQuery())
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->withPHIDs($seen_phids)
        ->needProfileImage(true)
        ->execute();
      if (count($seen_data)) {
        foreach ($seen_data as $s_data) {
         $seen_user[] = array(
           'phid' => $s_data->getPHID(),
           'username' => $s_data->getUserName(),
           'fullname' => $s_data->getFullName(),
           'profileImageURI' => $s_data->getProfileImageURI(),
         );
        }
      }
    }

    $engine = PhabricatorMarkupEngine::getEngine()
      ->setConfig('viewer', PhabricatorUser::getOmnipotentUser());

    $parsed_content = $engine->markupText($this->getContent());
    if ($parsed_content instanceof PhutilSafeHTML) {
      $parsed_content = $parsed_content->getHTMLContent();
    }

    return array(
      'title' => $this->getTitle(),
      'noteType' => $this->getNoteType(),
      'content' => $this->getContent(),
      'htmlContent' => $parsed_content,
      'owner' => [
        'phid' => $user->getPHID(),
        'username' => $user->getUserName(),
        'fullname' => $user->getFullName(),
        'profileImageURI' => $user->getProfileImageURI(),
      ],
      'seenCount' => count($this->seenPHIDs),
      'seenProfile' => $seen_user,
    );
  }

  public function conduitGoals() {
    $user = $this->loadUser();
    $seen_phids = $this->seenPHIDs;
    $seen_user = [];
    if ($seen_phids) {
      $seen_data = id(new PhabricatorPeopleQuery())
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->withPHIDs($seen_phids)
        ->needProfileImage(true)
        ->execute();
      if (count($seen_data)) {
        foreach ($seen_data as $s_data) {
         $seen_user[] = array(
           'phid' => $s_data->getPHID(),
           'username' => $s_data->getUserName(),
           'fullname' => $s_data->getFullName(),
           'profileImageURI' => $s_data->getProfileImageURI(),
         );
        }
      }
    }

    $engine = PhabricatorMarkupEngine::getEngine()
      ->setConfig('viewer', PhabricatorUser::getOmnipotentUser());

    $parsed_content = $engine->markupText($this->getContent());
    if ($parsed_content instanceof PhutilSafeHTML) {
      $parsed_content = $parsed_content->getHTMLContent();
    }
    $conph_phid = PhabricatorEdgeQuery::loadDestinationPHIDs(
      $this->getPHID(),
    LobbyGoalsHasRoomEdgeType::EDGECONST);
    $project_phids = null;

    if (!empty($conph_phid)) {
      $project_phids = id(new ConpherenceThreadQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withPHIDs($conph_phid)
      ->execute();
      $project_phids = mpull($project_phids, 'getTagsPHID');
      $project_phids = $project_phids[end($conph_phid)];
    }
    $result = array(
      'title' => $this->getTitle(),
      'noteType' => $this->getNoteType(),
      'content' => $this->getContent(),
      'conph' =>  array_pop($conph_phid),
      'project' => $project_phids,
      'htmlContent' => $parsed_content,
      'owner' => [
        'phid' => $user->getPHID(),
        'username' => $user->getUserName(),
        'fullname' => $user->getFullName(),
        'profileImageURI' => $user->getProfileImageURI(),
      ],
      'seenCount' => count($this->seenPHIDs),
      'seenProfile' => $seen_user,
    );
    $result = array_merge($result, $this->getManiphest());
    return $result;
  }

  public function getManiphest() {
    $result = array();
    $task_phids = PhabricatorEdgeQuery::loadDestinationPHIDs(
      $this->getPHID(),
      LobbyGoalsHasManiphestEdgeType::EDGECONST);
    if (empty($task_phids)) {
      $result['maniphest'][] = array();
    } else {
      $tasks = id(new ManiphestTaskQuery())
              ->setViewer(PhabricatorUser::getOmnipotentUser())
              ->withPHIDs($task_phids)
              ->execute();
      foreach ($tasks as $task) {
        $status_value = $task->getStatus();
        $status_info = array(
          'value' => $status_value,
          'name' => ManiphestTaskStatus::getTaskStatusName($status_value),
          'color' => ManiphestTaskStatus::getStatusColor($status_value),
        );
        $owner_name = id(new PhabricatorPeopleQuery())
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->withPHIDs(array($task->getOwnerPHID()))
        ->executeOne();

        if ($owner_name) {
          $owner_name = $owner_name->getUserName();
        }

        $owner_name_qa = id(new PhabricatorPeopleQuery())
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->withPHIDs(array($task->getOwnerQAPHID()))
        ->executeOne();

        if ($owner_name_qa) {
          $owner_name_qa = $owner_name_qa->getUserName();
        }

        $maniphest_task = array();
        $maniphest_task['id'] = $task->getID();
        $maniphest_task['phid'] = $task->getPHID();
        $maniphest_task['title'] = $task->getTitle();
        $maniphest_task['assigned'] = $owner_name;
        $maniphest_task['tester'] = $owner_name_qa;
        $maniphest_task['points'] = $task->getPoints();
        $maniphest_task['pointsQA'] = $task->getPointsQA();
        $maniphest_task['status'] =  $status_info;
        $result['maniphest'][] = $maniphest_task;
      }
    }
    return $result;

  }

  public function getConduitSearchAttachments() {
    return array();
  }
}
