<?php

final class ManiphestTask extends ManiphestDAO
  implements
    PhabricatorSubscribableInterface,
    PhabricatorMarkupInterface,
    PhabricatorPolicyInterface,
    PhabricatorTokenReceiverInterface,
    PhabricatorFlaggableInterface,
    PhabricatorMentionableInterface,
    PhrequentTrackableInterface,
    PhabricatorCustomFieldInterface,
    PhabricatorDestructibleInterface,
    PhabricatorApplicationTransactionInterface,
    PhabricatorProjectInterface,
    PhabricatorSpacesInterface,
    PhabricatorConduitResultInterface,
    PhabricatorFulltextInterface,
    PhabricatorFerretInterface,
    DoorkeeperBridgedObjectInterface,
    PhabricatorEditEngineSubtypeInterface,
    PhabricatorEditEngineLockableInterface,
    PhabricatorEditEngineMFAInterface,
    PhabricatorPolicyCodexInterface,
    PhabricatorUnlockableInterface {

  const MARKUP_FIELD_DESCRIPTION = 'markup:desc';

  protected $authorPHID;
  protected $ownerQAPHID;
  protected $ownerPHID;

  protected $status;
  protected $priority;
  protected $subpriority = 0;

  protected $title = '';
  protected $description = '';
  protected $originalEmailSource;
  protected $mailKey;
  protected $viewPolicy = PhabricatorPolicies::POLICY_USER;
  protected $editPolicy = PhabricatorPolicies::POLICY_USER;

  protected $ownerOrdering;
  protected $ownerQAOrdering;
  protected $spacePHID;
  protected $bridgedObjectPHID;
  protected $properties = array();
  protected $points;
  protected $pointsQA;
  protected $subtype;
  protected $progress;

  protected $closedEpoch;
  protected $closerPHID;

  private $subscriberPHIDs = self::ATTACHABLE;
  private $groupByProjectPHID = self::ATTACHABLE;
  private $customFields = self::ATTACHABLE;
  private $edgeProjectPHIDs = self::ATTACHABLE;
  private $bridgedObject = self::ATTACHABLE;
  private $viewer;

  public static function initializeNewTask(PhabricatorUser $actor) {
    $app = id(new PhabricatorApplicationQuery())
      ->setViewer($actor)
      ->withClasses(array('PhabricatorManiphestApplication'))
      ->executeOne();

    $view_policy = $app->getPolicy(ManiphestDefaultViewCapability::CAPABILITY);
    $edit_policy = $app->getPolicy(ManiphestDefaultEditCapability::CAPABILITY);

    return id(new ManiphestTask())
      ->setStatus(ManiphestTaskStatus::getDefaultStatus())
      ->setPriority(ManiphestTaskPriority::getDefaultPriority())
      ->setAuthorPHID($actor->getPHID())
      ->setViewPolicy($view_policy)
      ->setEditPolicy($edit_policy)
      ->setSpacePHID($actor->getDefaultSpacePHID())
      ->setPoints(1)
      ->setProgress(0)
      ->setSubtype(PhabricatorEditEngineSubtype::SUBTYPE_DEFAULT)
      ->attachProjectPHIDs(array())
      ->attachSubscriberPHIDs(array());
  }

  protected function getConfiguration() {
    return array(
      self::CONFIG_AUX_PHID => true,
      self::CONFIG_SERIALIZATION => array(
        'properties' => self::SERIALIZATION_JSON,
      ),
      self::CONFIG_COLUMN_SCHEMA => array(
        'ownerPHID' => 'phid?',
        'ownerQAPHID' => 'phid?',
        'status' => 'text64',
        'priority' => 'uint32',
        'title' => 'sort',
        'description' => 'text',
        'mailKey' => 'bytes20',
        'ownerOrdering' => 'text64?',
        'ownerQAOrdering' => 'text64?',
        'originalEmailSource' => 'text255?',
        'subpriority' => 'double',
        'points' => 'double?',
        'pointsQA' => 'double?',
        'progress' => 'uint32',
        'bridgedObjectPHID' => 'phid?',
        'subtype' => 'text64',
        'closedEpoch' => 'epoch?',
        'closerPHID' => 'phid?',
      ),
      self::CONFIG_KEY_SCHEMA => array(
        'key_phid' => null,
        'phid' => array(
          'columns' => array('phid'),
          'unique' => true,
        ),
        'priority' => array(
          'columns' => array('priority', 'status'),
        ),
        'status' => array(
          'columns' => array('status'),
        ),
        'ownerPHID' => array(
          'columns' => array('ownerPHID', 'status'),
        ),
        'authorPHID' => array(
          'columns' => array('authorPHID', 'status'),
        ),
        'ownerOrdering' => array(
          'columns' => array('ownerOrdering'),
        ),
        'priority_2' => array(
          'columns' => array('priority', 'subpriority'),
        ),
        'key_dateCreated' => array(
          'columns' => array('dateCreated'),
        ),
        'key_dateModified' => array(
          'columns' => array('dateModified'),
        ),
        'key_title' => array(
          'columns' => array('title(64)'),
        ),
        'key_bridgedobject' => array(
          'columns' => array('bridgedObjectPHID'),
          'unique' => true,
        ),
        'key_subtype' => array(
          'columns' => array('subtype'),
        ),
        'key_closed' => array(
          'columns' => array('closedEpoch'),
        ),
        'key_closer' => array(
          'columns' => array('closerPHID', 'closedEpoch'),
        ),
      ),
    ) + parent::getConfiguration();
  }

  public function loadDependsOnTaskPHIDs() {
    return PhabricatorEdgeQuery::loadDestinationPHIDs(
      $this->getPHID(),
      ManiphestTaskDependsOnTaskEdgeType::EDGECONST);
  }

  public function loadDependedOnByTaskPHIDs() {
    return PhabricatorEdgeQuery::loadDestinationPHIDs(
      $this->getPHID(),
      ManiphestTaskDependedOnByTaskEdgeType::EDGECONST);
  }

  public function generatePHID() {
    return PhabricatorPHID::generateNewPHID(ManiphestTaskPHIDType::TYPECONST);
  }

  public function getSubscriberPHIDs() {
    return $this->assertAttached($this->subscriberPHIDs);
  }

  public function getProjectPHIDs() {
    return $this->assertAttached($this->edgeProjectPHIDs);
  }

  public function attachProjectPHIDs(array $phids) {
    $this->edgeProjectPHIDs = $phids;
    return $this;
  }

  public function attachSubscriberPHIDs(array $phids) {
    $this->subscriberPHIDs = $phids;
    return $this;
  }

  public function setOwnerPHID($phid) {
    $this->ownerPHID = nonempty($phid, null);
    return $this;
  }

  public function setOwnerQAPHID($phid) {
    $this->ownerQAPHID = nonempty($phid, null);
    return $this;
  }

  public function getMonogram() {
    return 'T'.$this->getID();
  }

  public function getURI() {
    return '/'.$this->getMonogram();
  }

  public function setViewer(PhabricatorUser $viewer) {
    $this->viewer = $viewer;
    return $this;
  }

  public function getViewer() {
    return $this->viewer;
  }

  public function attachGroupByProjectPHID($phid) {
    $this->groupByProjectPHID = $phid;
    return $this;
  }

  public function getGroupByProjectPHID() {
    return $this->assertAttached($this->groupByProjectPHID);
  }

  public function save() {
    if (!$this->mailKey) {
      $this->mailKey = Filesystem::readRandomCharacters(20);
    }

    $result = parent::save();

    return $result;
  }

  public function isClosed() {
    return ManiphestTaskStatus::isClosedStatus($this->getStatus());
  }

  public function areCommentsLocked() {
    if ($this->areEditsLocked()) {
      return true;
    }

    return ManiphestTaskStatus::areCommentsLockedInStatus($this->getStatus());
  }

  public function areEditsLocked() {
    return ManiphestTaskStatus::areEditsLockedInStatus($this->getStatus());
  }

  public function setProperty($key, $value) {
    $this->properties[$key] = $value;
    return $this;
  }

  public function getProperty($key, $default = null) {
    return idx($this->properties, $key, $default);
  }

  public function getCoverImageFilePHID() {
    return idx($this->properties, 'cover.filePHID');
  }

  public function getCoverImageThumbnailPHID() {
    return idx($this->properties, 'cover.thumbnailPHID');
  }

  public function getPriorityKeyword() {
    $priority = $this->getPriority();

    $keyword = ManiphestTaskPriority::getKeywordForTaskPriority($priority);
    if ($keyword !== null) {
      return $keyword;
    }

    return ManiphestTaskPriority::UNKNOWN_PRIORITY_KEYWORD;
  }


/* -(  PhabricatorSubscribableInterface  )----------------------------------- */


  public function isAutomaticallySubscribed($phid) {
    return ($phid == $this->getOwnerPHID());
  }



/* -(  Markup Interface  )--------------------------------------------------- */


  /**
   * @task markup
   */
  public function getMarkupFieldKey($field) {
    $content = $this->getMarkupText($field);
    return PhabricatorMarkupEngine::digestRemarkupContent($this, $content);
  }


  /**
   * @task markup
   */
  public function getMarkupText($field) {
    return $this->getDescription();
  }


  /**
   * @task markup
   */
  public function newMarkupEngine($field) {
    return PhabricatorMarkupEngine::newManiphestMarkupEngine();
  }


  /**
   * @task markup
   */
  public function didMarkupText(
    $field,
    $output,
    PhutilMarkupEngine $engine) {
    return $output;
  }


  /**
   * @task markup
   */
  public function shouldUseMarkupCache($field) {
    return (bool)$this->getID();
  }


/* -(  Policy Interface  )--------------------------------------------------- */


  public function getCapabilities() {
    return array(
      PhabricatorPolicyCapability::CAN_VIEW,
      PhabricatorPolicyCapability::CAN_INTERACT,
      PhabricatorPolicyCapability::CAN_EDIT,
    );
  }

  public function getPolicy($capability) {
    switch ($capability) {
      case PhabricatorPolicyCapability::CAN_VIEW:
        return $this->getViewPolicy();
      case PhabricatorPolicyCapability::CAN_INTERACT:
        if ($this->areCommentsLocked()) {
          return PhabricatorPolicies::POLICY_NOONE;
        } else {
          return $this->getViewPolicy();
        }
      case PhabricatorPolicyCapability::CAN_EDIT:
        if ($this->areEditsLocked()) {
          return PhabricatorPolicies::POLICY_NOONE;
        } else {
          return $this->getEditPolicy();
        }
    }
  }

  public function hasAutomaticCapability($capability, PhabricatorUser $user) {
    // The owner of a task can always view and edit it.
    $owner_phid = $this->getOwnerPHID();
    if ($owner_phid) {
      $user_phid = $user->getPHID();
      if ($user_phid == $owner_phid) {
        return true;
      }
    }

    return false;
  }


/* -(  PhabricatorTokenReceiverInterface  )---------------------------------- */


  public function getUsersToNotifyOfTokenGiven() {
    // Sort of ambiguous who this was intended for; just let them both know.
    return array_filter(
      array_unique(
        array(
          $this->getAuthorPHID(),
          $this->getOwnerPHID(),
        )));
  }


/* -(  PhabricatorCustomFieldInterface  )------------------------------------ */


  public function getCustomFieldSpecificationForRole($role) {
    return PhabricatorEnv::getEnvConfig('maniphest.fields');
  }

  public function getCustomFieldBaseClass() {
    return 'ManiphestCustomField';
  }

  public function getCustomFields() {
    return $this->assertAttached($this->customFields);
  }

  public function attachCustomFields(PhabricatorCustomFieldAttachment $fields) {
    $this->customFields = $fields;
    return $this;
  }


/* -(  PhabricatorDestructibleInterface  )----------------------------------- */


  public function destroyObjectPermanently(
    PhabricatorDestructionEngine $engine) {

    $this->openTransaction();
    $this->delete();
    $this->saveTransaction();
  }


/* -(  PhabricatorApplicationTransactionInterface  )------------------------- */


  public function getApplicationTransactionEditor() {
    return new ManiphestTransactionEditor();
  }

  public function getApplicationTransactionTemplate() {
    return new ManiphestTransaction();
  }


/* -(  PhabricatorSpacesInterface  )----------------------------------------- */


  public function getSpacePHID() {
    return $this->spacePHID;
  }


/* -(  PhabricatorConduitResultInterface  )---------------------------------- */


  public function getFieldSpecificationsForConduit() {
    return array(
      id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('title')
        ->setType('string')
        ->setDescription(pht('The title of the task.')),
      id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('description')
        ->setType('remarkup')
        ->setDescription(pht('The task description.')),
      id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('authorPHID')
        ->setType('phid')
        ->setDescription(pht('Original task author.')),
      id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('ownerPHID')
        ->setType('phid?')
        ->setDescription(pht('Current task owner, if task is assigned.')),
      id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('ownerQAPHID')
        ->setType('phid?')
        ->setDescription(pht('Current task QA owner, if task is assigned.')),
      id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('status')
        ->setType('map<string, wild>')
        ->setDescription(pht('Information about task status.')),
      id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('priority')
        ->setType('map<string, wild>')
        ->setDescription(pht('Information about task priority.')),
      id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('points')
        ->setType('points')
        ->setDescription(pht('Point value of the task.')),
      id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('pointsQA')
        ->setType('pointsQA')
        ->setDescription(pht('Point value of the task for QA.')),
      id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('progress')
        ->setType('int')
        ->setDescription(pht('Current progress of the task.')),
      id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('subtype')
        ->setType('string')
        ->setDescription(pht('Subtype of the task.')),
      id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('closerPHID')
        ->setType('phid?')
        ->setDescription(
          pht('User who closed the task, if the task is closed.')),
      id(new PhabricatorConduitSearchFieldSpecification())
        ->setKey('dateClosed')
        ->setType('int?')
        ->setDescription(
          pht('Epoch timestamp when the task was closed.')),
    );
  }

  public function getFieldValuesForConduit() {
    $status_value = $this->getStatus();
    $status_info = array(
      'value' => $status_value,
      'name' => ManiphestTaskStatus::getTaskStatusName($status_value),
      'color' => ManiphestTaskStatus::getStatusColor($status_value),
    );

    $priority_value = (int)$this->getPriority();
    $priority_info = array(
      'value' => $priority_value,
      'name' =>
        ManiphestTaskPriority::getTaskPriorityName($priority_value),
      'color' =>
        ManiphestTaskPriority::getTaskPriorityColor($priority_value),
    );

    $closed_epoch = $this->getClosedEpoch();
    if ($closed_epoch !== null) {
      $closed_epoch = (int)$closed_epoch;
    }

    $diffs = array();

    /**
     * will be removed soon
     */
    $author_diff_deprecated = array();
    /**
     * end
     */

    $author_diff = array();
    $author_diffs = array();

    $edge_query = id(new PhabricatorEdgeQuery())
      ->withSourcePHIDs(array($this->getPHID()))
      ->withEdgeTypes(
        array(
          ManiphestTaskHasRevisionEdgeType::EDGECONST,
        ));

    $edge_query->execute();
    if ($edge_query) {
      $diff_phids = $edge_query->getDestinationPHIDs(array($this->getPHID()));
      if ($diff_phids) {
        $diffs = id(new DifferentialRevisionQuery())
          ->setViewer($this->getViewer())
          ->withPHIDs($diff_phids)
          ->withAuthors(array($this->getViewer()->getPHID()))
          ->execute();

        $author_diff_deprecated = array_keys(mpull($diffs, null, 'getPHID'));
        if ($diffs) {
          foreach ($diffs as $diff) {
            $author_diff['id'] = 'D'.$diff->getID();
            $author_diff['phid'] = $diff->getPHID();
            $author_diff['status'] = $diff->getStatus();
            $author_diff['name'] = $diff->getTitle();
            $author_diffs[] = $author_diff;
          }
        }
      }
    }

    $engine = PhabricatorMarkupEngine::getEngine()
                  ->setConfig('viewer', PhabricatorUser::getOmnipotentUser());
    $parsed_description = $engine->markupText($this->getDescription());
    if ($parsed_description instanceof PhutilSafeHTML) {
      $parsed_description = $parsed_description->getHTMLContent();
    }

    $position_name = $this->getColumnPosition($this, $this->getViewer());
    $positions_names = $this->getColumnPositions($this, $this->getViewer());

    $parent_tasks = $this->getParentTasks($this);
    $sub_tasks = $this->getSubTasks($this);
    $reviewer_name = null;
    $reviewer_phid = $this->getCustomReviewer($this, $this->getViewer());
    if ($reviewer_phid) {
      $reviewer_name = id(new PhabricatorPeopleQuery())
        ->setViewer($this->getViewer())
        ->withPHIDs([$reviewer_phid])
        ->executeOne();
      $reviewer_name = $reviewer_name->getUserName();
    }
    $owner_name = id(new PhabricatorPeopleQuery())
    ->setViewer($this->getViewer())
    ->withPHIDs(array($this->getOwnerPHID()))
    ->executeOne();

    if ($owner_name) {
      $owner_name = $owner_name->getUserName();
    }

    $owner_name_qa = id(new PhabricatorPeopleQuery())
    ->setViewer($this->getViewer())
    ->withPHIDs(array($this->getOwnerQAPHID()))
    ->executeOne();

    if ($owner_name_qa) {
      $owner_name_qa = $owner_name_qa->getUserName();
    }

    return array(
      'name' => $this->getTitle(),
      'description' => array(
        'raw' => $this->getDescription(),
      ),
      'htmlDescription' => $parsed_description,
      'differentialPHIDs' => $author_diff_deprecated,
      'differentials' => $author_diffs,
      'parents' => $parent_tasks,
      'subTasks' => $sub_tasks,
      'assigned' => $owner_name,
      'tester' => $owner_name_qa,
      'reviewer' => $reviewer_name,
      'authorPHID' => $this->getAuthorPHID(),
      'ownerPHID' => $this->getOwnerPHID(),
      'ownerQAPHID' => $this->getOwnerQAPHID(),
      'position' => $position_name,
      'positions' => $positions_names,
      'status' => $status_info,
      'priority' => $priority_info,
      'points' => $this->getPoints(),
      'pointsQA' => $this->getPointsQA(),
      'progress' => (int)$this->getProgress(),
      'subtype' => $this->getSubtype(),
      'closerPHID' => $this->getCloserPHID(),
      'dateClosed' => $closed_epoch,
    );
  }

  public function getConduitSearchAttachments() {
    return array(
      id(new PhabricatorBoardColumnsSearchEngineAttachment())
        ->setAttachmentKey('columns'),
      id(new PhabricatorProjectsLogsSearchEngineAttachment())
        ->setAttachmentKey('logs'),
      id(new ManiphestAssignmentsSearchEngineAttachment())
        ->setAttachmentKey('assignments'),
      id(new PhabricatorTicketStatusesSearchEngineAttachment())
        ->setAttachmentKey('statuses'),
      id(new PhabricatorTicketDescriptionsSearchEngineAttachment())
        ->setAttachmentKey('descriptionLog'),
      id(new PhabricatorTicketTitlesSearchEngineAttachment())
        ->setAttachmentKey('titleLog'),
      id(new PhabricatorTicketPointsSearchEngineAttachment())
        ->setAttachmentKey('pointLog'),
      id(new PhabricatorBounceSearchEngineAttachment())
        ->setAttachmentKey('bounce'),
    );
  }

  public function newSubtypeObject() {
    $subtype_key = $this->getEditEngineSubtype();
    $subtype_map = $this->newEditEngineSubtypeMap();
    return $subtype_map->getSubtype($subtype_key);
  }

  private function getCustomReviewer($task, $viewer) {
    $field_list = PhabricatorCustomField::getObjectFields(
      $task,
      PhabricatorCustomField::ROLE_VIEW);

    $field_list
      ->setViewer($viewer)
      ->readFieldsFromStorage($task);

    $reviewer_phid = null;

    if ($field_list) {
      foreach ($field_list->getFields() as $key => $value) {
        $field = explode(':', $key);

        if (end($field) == 'reviewer' &&
          !is_null($value->getProxy()->getFieldValue())) {
          $reviewer_phid = $value->getProxy()->getFieldValue()[0];
        }
      }
    }
    return $reviewer_phid;
  }

  private function getParentTasks($task) {
    $parent_phids = array();
    $parent_task = array();
    $parent_tasks = array();
    $parent_edge_query = id(new PhabricatorEdgeQuery())
    ->withSourcePHIDs(array($task->getPHID()))
    ->withEdgeTypes(
      array(
        ManiphestTaskDependedOnByTaskEdgeType::EDGECONST,
      ));

    $parent_edge_query->execute();
    if ($parent_edge_query) {
      $parent_phids = $parent_edge_query->getDestinationPHIDs(
        array($task->getPHID()));

      if ($parent_phids) {
        $parents = id(new ManiphestTaskQuery())
          ->setViewer($task->getViewer())
          ->withPHIDs($parent_phids)
          ->execute();

        foreach ($parents as $parent) {
          $status_value = $parent->getStatus();
          $status_info = array(
            'value' => $status_value,
            'name' => ManiphestTaskStatus::getTaskStatusName($status_value),
            'color' => ManiphestTaskStatus::getStatusColor($status_value),
          );
          $reviewer_name = null;
          $reviewer_phid =
            $this->getCustomReviewer($parent, $this->getViewer());
          if ($reviewer_phid) {
            $reviewer_name = id(new PhabricatorPeopleQuery())
              ->setViewer($this->getViewer())
              ->withPHIDs([$reviewer_phid])
              ->executeOne();
            $reviewer_name = $reviewer_name->getUserName();
          }
          $priority_value = (int)$task->getPriority();
          $priority_info = array(
            'value' => $priority_value,
            'name' =>
            ManiphestTaskPriority::getTaskPriorityName($priority_value),
            'color' =>
            ManiphestTaskPriority::getTaskPriorityColor($priority_value),
          );

          $owner_name = id(new PhabricatorPeopleQuery())
          ->setViewer($this->getViewer())
          ->withPHIDs(array($parent->getOwnerPHID()))
          ->executeOne();

          if ($owner_name) {
            $owner_name = $owner_name->getUserName();
          }

          $owner_name_qa = id(new PhabricatorPeopleQuery())
          ->setViewer($this->getViewer())
          ->withPHIDs(array($parent->getOwnerQAPHID()))
          ->executeOne();

          if ($owner_name_qa) {
            $owner_name_qa = $owner_name_qa->getUserName();
          }


          $parent_task['id'] = $parent->getID();
          $parent_task['phid'] = $parent->getPHID();
          $parent_task['title'] = $parent->getTitle();
          $parent_task['authorPHID'] = $parent->getAuthorPHID();
          $parent_task['ownerPHID'] = $parent->getOwnerPHID();
          $parent_task['ownerQAPHID'] = $parent->getOwnerQAPHID();
          $parent_task['assigned'] = $owner_name;
          $parent_task['tester'] = $owner_name_qa;
          $parent_task['reviewer'] = $reviewer_name;
          $parent_task['status'] = $status_info;
          $parent_task['priority'] = $priority_info;
          $parent_task['custom.reviewer'] = $reviewer_phid;

          $parent_tasks[] = $parent_task;
        }
      }
    }

    return $parent_tasks;
  }

  private function getSubTasks($task) {
    $sub_phids = array();
    $sub_task = array();
    $sub_tasks = array();
    $sub_edge_query = id(new PhabricatorEdgeQuery())
    ->withSourcePHIDs(array($task->getPHID()))
    ->withEdgeTypes(
      array(
        ManiphestTaskDependsOnTaskEdgeType::EDGECONST,
      ));

    $sub_edge_query->execute();
    if ($sub_edge_query) {
      $sub_phids = $sub_edge_query->getDestinationPHIDs(
        array($task->getPHID()));

      if ($sub_phids) {
        $subs = id(new ManiphestTaskQuery())
          ->setViewer($task->getViewer())
          ->withPHIDs($sub_phids)
          ->execute();

        foreach ($subs as $sub) {
          $status_value = $sub->getStatus();
          $status_info = array(
            'value' => $status_value,
            'name' => ManiphestTaskStatus::getTaskStatusName($status_value),
            'color' => ManiphestTaskStatus::getStatusColor($status_value),
          );

          $priority_value = (int)$task->getPriority();
          $priority_info = array(
            'value' => $priority_value,
            'name' => ManiphestTaskPriority::getTaskPriorityName($priority_value),
            'color' => ManiphestTaskPriority::getTaskPriorityColor($priority_value),
          );
          $reviewer_name = null;
          $reviewer_phid = $this->getCustomReviewer($sub, $this->getViewer());
          if ($reviewer_phid) {
            $reviewer_name = id(new PhabricatorPeopleQuery())
              ->setViewer($this->getViewer())
              ->withPHIDs([$reviewer_phid])
              ->executeOne();
            $reviewer_name = $reviewer_name->getUserName();
          }

          $owner_name = id(new PhabricatorPeopleQuery())
          ->setViewer($this->getViewer())
          ->withPHIDs(array($sub->getOwnerPHID()))
          ->executeOne();

          if ($owner_name) {
            $owner_name = $owner_name->getUserName();
          }

          $owner_name_qa = id(new PhabricatorPeopleQuery())
          ->setViewer($this->getViewer())
          ->withPHIDs(array($sub->getOwnerQAPHID()))
          ->executeOne();

          if ($owner_name_qa) {
            $owner_name_qa = $owner_name_qa->getUserName();
          }


          $sub_task['id'] = $sub->getID();
          $sub_task['phid'] = $sub->getPHID();
          $sub_task['title'] = $sub->getTitle();
          $sub_task['authorPHID'] = $sub->getAuthorPHID();
          $sub_task['ownerPHID'] = $sub->getOwnerPHID();
          $sub_task['ownerQAPHID'] = $sub->getOwnerQAPHID();
          $sub_task['assigned'] = $owner_name;
          $sub_task['tester'] = $owner_name_qa;
          $sub_task['reviewer'] = $reviewer_name;
          $sub_task['status'] = $status_info;
          $sub_task['priority'] = $priority_info;
          $sub_task['custom.reviewer'] = $reviewer_phid;
          $sub_tasks[] = $sub_task;
        }
      }
    }

    return $sub_tasks;
  }

  public function getColumnPosition($task, $viewer) {
    $project_edge_query = id(new PhabricatorEdgeQuery())
    ->withSourcePHIDs(array($task->getPHID()))
    ->withEdgeTypes(
      array(
        PhabricatorProjectObjectHasProjectEdgeType::EDGECONST,
      ));

    $project_edge_query->execute();

    $project_phids = array();
    $position_name = 'Backlog';
    if ($project_edge_query) {
      $project_phids = $project_edge_query->getDestinationPHIDs(
        array($task->getPHID()));
      if ($project_phids) {
        $column = id(new PhabricatorProjectColumnPositionQuery())
          ->setViewer($viewer)
          ->withBoardPHIDs($project_phids)
          ->withObjectPHIDs(array($task->getPHID()))
          ->execute();

        if ($column) {
          $column_first = current($column);
        } else {
          $column_first = $column;
        }

        if ($column_first) {
          $position = id(new PhabricatorProjectColumnQuery())
            ->setViewer($viewer)
            ->withPHIDs(array($column_first->getColumnPHID()))
            ->executeOne();

          if ($position->getName() == null) {
            $position_name = 'Backlog';
          } else {
            $position_name = $position->getName();
          }
        }
      }
    }
    return $position_name;
  }

  public function getColumnPositions($task, $viewer) {
    $project_edge_query = id(new PhabricatorEdgeQuery())
      ->withSourcePHIDs(array($task->getPHID()))
      ->withEdgeTypes(array(
          PhabricatorProjectObjectHasProjectEdgeType::EDGECONST,
        ));

    $project_edge_query->execute();

    $project_phids = array();
    $position_name = array();
    $column = array();

    if ($project_edge_query) {
      $project_phids = $project_edge_query->getDestinationPHIDs(
        array($task->getPHID()));
      if ($project_phids) {
        $column = id(new PhabricatorProjectColumnPositionQuery())
          ->setViewer($viewer)
          ->withBoardPHIDs($project_phids)
          ->withObjectPHIDs(array($task->getPHID()))
          ->execute();

        if ($column) {
          foreach ($column as $key => $value) {
            $position = id(new PhabricatorProjectColumnQuery())
              ->setViewer($viewer)
              ->withPHIDs(array($value->getColumnPHID()))
              ->executeOne();

            if ($position->getName() == null) {
              $position_name[$position->getProject()->getPHID()] = 'Backlog';
            } else {
              $position_name[$position->getProject()->getPHID()]
                = $position->getName();
            }
          }
        }
      }
    } else {
      $position_name = [''];
    }
    return $position_name;
  }

  public function getSubscribers($task, $viewer) {
    $subscriber = array();
    $subscribers = array();
    $subscriber_edge_query = id(new PhabricatorEdgeQuery())
    ->withSourcePHIDs(array($task->getPHID()))
    ->withEdgeTypes(
      array(
        PhabricatorObjectHasSubscriberEdgeType::EDGECONST,
      ));

    $subscriber_edge_query->execute();
    $user_phids = array();
    if ($subscriber_edge_query) {
      $user_phids = $subscriber_edge_query->getDestinationPHIDs(
        array($task->getPHID()));

      if ($user_phids) {
        $users = id(new PhabricatorPeopleQuery())
          ->setViewer($viewer)
          ->needProfileImage(true)
          ->withPHIDs($user_phids)
          ->execute();

        foreach ($users as $user) {
          $subscriber['id'] = $user->getID();
          $subscriber['phid'] = $user->getPHID();
          $subscriber['username'] = $user->getUsername();
          $subscriber['realname'] = $user->getRealname();
          $subscriber['profileImageURI'] = $user->getProfileImageURI();
          $subscribers[] = $subscriber;
        }
      }
    }

    return $subscribers;
  }

/* -(  PhabricatorFulltextInterface  )--------------------------------------- */


  public function newFulltextEngine() {
    return new ManiphestTaskFulltextEngine();
  }


/* -(  DoorkeeperBridgedObjectInterface  )----------------------------------- */


  public function getBridgedObject() {
    return $this->assertAttached($this->bridgedObject);
  }

  public function attachBridgedObject(
    DoorkeeperExternalObject $object = null) {
    $this->bridgedObject = $object;
    return $this;
  }


/* -(  PhabricatorEditEngineSubtypeInterface  )------------------------------ */


  public function getEditEngineSubtype() {
    return $this->getSubtype();
  }

  public function setEditEngineSubtype($value) {
    return $this->setSubtype($value);
  }

  public function newEditEngineSubtypeMap() {
    $config = PhabricatorEnv::getEnvConfig('maniphest.subtypes');
    return PhabricatorEditEngineSubtype::newSubtypeMap($config)
      ->setDatasource(new ManiphestTaskSubtypeDatasource());
  }


/* -(  PhabricatorEditEngineLockableInterface  )----------------------------- */


  public function newEditEngineLock() {
    return new ManiphestTaskEditEngineLock();
  }


/* -(  PhabricatorFerretInterface  )----------------------------------------- */


  public function newFerretEngine() {
    return new ManiphestTaskFerretEngine();
  }


/* -(  PhabricatorEditEngineMFAInterface  )---------------------------------- */


  public function newEditEngineMFAEngine() {
    return new ManiphestTaskMFAEngine();
  }


/* -(  PhabricatorPolicyCodexInterface  )------------------------------------ */


  public function newPolicyCodex() {
    return new ManiphestTaskPolicyCodex();
  }


/* -(  PhabricatorUnlockableInterface  )------------------------------------- */


  public function newUnlockEngine() {
    return new ManiphestTaskUnlockEngine();
  }

}
