<?php

final class UserTaskConduitAPIMethod extends UserConduitAPIMethod {

  public function getAPIMethodName() {
    return 'user.tasks';
  }

  public function newSearchEngine() {
    return new ManiphestTaskSearchEngine();
  }

  public function getMethodDescription() {
    return 'Conduit with function to find every tasks of a user.';
  }

  public function getMethodDocumentation() {
    $viewer = $this->getViewer();

    $engine = $this->newSearchEngine()->setViewer($viewer);
    $query = $engine->newQuery();

    $out = array();

    $out[] = $this->buildConstraintsBox($engine);

    return $out;
  }

  protected function defineParamTypes() {
    return array(
        'constraints' => 'optional list<string>',
        'dateStart' => 'optional dateStart',
        'dateEnd' => 'optional dateEnd',
      ) + $this->getPagerParamTypes();
  }

  protected function defineReturnType() {
    return 'list<dict>';
  }

  private function buildConstraintsBox(
    PhabricatorApplicationSearchEngine $engine) {

    $info = pht(<<<EOTEXT
You can apply custom constraints by passing a dictionary in `constraints`.
This will let you search for specific sets of results (for example, you may
want show only results with a certain state, status, or owner).
If you specify both a `queryKey` and `constraints`, the builtin or saved query
will be applied first as a starting point, then any additional values in
`constraints` will be applied, overwriting the defaults from the original query.
Different endpoints support different constraints. The constraints this method
supports are detailed below. As an example, you might specify constraints like
this:
```lang=json, name="Example Custom Constraints"
{
  ...
  "constraints": {
    "phids":["PHID-XACT-1111","PHID-XACT-2222"],
    "authorPHIDs": ["PHID-USER-1111", "PHID-USER-2222"],
    ...
  },
  ...
}
```
This API endpoint supports these constraints:
EOTEXT
    );

    $fields = $engine->getSearchFieldsForConduit();

    // As a convenience, put these fields at the very top, even if the engine
    // specifies and alternate display order for the web UI. These fields are
    // very important in the API and nearly useless in the web UI.
    $fields = array_select_keys(
      $fields,
      array('phids', 'authorPHIDs'));

    $constant_lists = array();

    $rows = array();
    foreach ($fields as $field) {
      $key = $field->getConduitKey();
      $label = $field->getLabel();

      $constants = $field->newConduitConstants();
      $show_table = false;

      $type_object = $field->getConduitParameterType();
      if ($type_object) {
        $type = $type_object->getTypeName();
        $description = $field->getDescription();
        if ($constants) {
          $description = array(
            $description,
            ' ',
            phutil_tag('em', array(), pht('(See table below.)')),
          );
          $show_table = true;
        }
      } else {
        $type = null;
        $description = phutil_tag('em', array(), pht('Not supported.'));
      }

      $rows[] = array(
        $key,
        $label,
        $type,
        $description,
      );

      if ($show_table) {
        $constant_lists[] = $this->newRemarkupDocumentationView(
          pht(
            'Constants supported by the `%s` constraint:',
            $key));

        $constants_rows = array();
        foreach ($constants as $constant) {
          if ($constant->getIsDeprecated()) {
            $icon = id(new PHUIIconView())
              ->setIcon('fa-exclamation-triangle', 'red');
          } else {
            $icon = null;
          }

          $constants_rows[] = array(
            $constant->getKey(),
            array(
              $icon,
              ' ',
              $constant->getValue(),
            ),
          );
        }

        $constants_table = id(new AphrontTableView($constants_rows))
          ->setHeaders(
            array(
              pht('Key'),
              pht('Value'),
            ))
          ->setColumnClasses(
            array(
              'mono',
              'wide',
            ));

        $constant_lists[] = $constants_table;
      }
    }

    $table = id(new AphrontTableView($rows))
      ->setHeaders(
        array(
          pht('Key'),
          pht('Label'),
          pht('Type'),
          pht('Description'),
        ))
      ->setColumnClasses(
        array(
          'prewrap',
          'pri',
          'prewrap',
          'wide',
        ));

    return id(new PHUIObjectBoxView())
      ->setHeaderText(pht('Custom Query Constraints'))
      ->setCollapsed(true)
      ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
      ->appendChild($this->newRemarkupDocumentationView($info))
      ->appendChild($table)
      ->appendChild($constant_lists);
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $request->getUser();
    $pager = $this->newPager($request);

    $object = $this->loadTemplateObject($request);

    $xaction_query = PhabricatorApplicationTransactionQuery::newQueryForObject(
      $object);

    $xaction_query
      ->needHandles(false)
      ->setViewer($viewer);

    if ($object->getPHID()) {
      $xaction_query->withObjectPHIDs(array($object->getPHID()));
    }

    $constraints = $request->getValue('constraints', array());
    $get_date_start = $request->getValue('dateStart') != null ?
      $request->getValue('dateStart') : 0;
    $get_date_end = $request->getValue('dateEnd') != null ?
      $request->getValue('dateEnd') : 9999999999999;

    $xaction_query = $this->applyConstraints($constraints, $xaction_query);
    $xaction_query = $this->applyDate(
      $get_date_start, $get_date_end, $xaction_query);
    $xaction_query = $this->applyTransactionType(
      LobbyStateCurrentTaskTransaction::TRANSACTIONTYPE, $xaction_query);

    $xactions = $xaction_query->executeWithCursorPager($pager);

    $modular_classes = array();
    $modular_objects = array();
    $modular_xactions = array();

    foreach ($xactions as $xaction) {
      if (!$xaction instanceof PhabricatorModularTransaction) {
        continue;
      }

      // Hack things so certain transactions which don't have a modular
      // type yet can use a pseudotype until they modularize. Some day, we'll
      // modularize everything and remove this.
      switch ($xaction->getTransactionType()) {
        case DifferentialTransaction::TYPE_INLINE:
          $modular_template = new DifferentialRevisionInlineTransaction();
          break;
        default:
          $modular_template = $xaction->getModularType();
          break;
      }

      $modular_class = get_class($modular_template);
      if (!isset($modular_objects[$modular_class])) {
        try {
          $modular_object = newv($modular_class, array());
          $modular_objects[$modular_class] = $modular_object;
        } catch (Throwable $e) {
          continue;
        }
      }

      $modular_classes[$xaction->getPHID()] = $modular_class;
      $modular_xactions[$modular_class][] = $xaction;
    }

    $modular_data_map = array();

    foreach ($modular_objects as $class => $modular_type) {
      $modular_data_map[$class] = $modular_type
        ->setViewer($viewer)
        ->loadTransactionTypeConduitData($modular_xactions[$class]);
    }

    $data = array();

    foreach ($xactions as $xaction) {

      $group_id = $xaction->getTransactionGroupID();

      if (!strlen($group_id)) {
        $group_id = null;
      } else {
        $group_id = (string)$group_id;
      }

      $user = null;
      if ((string)$xaction->getAuthorPHID()) {
        $author_info = id(new PhabricatorPeopleQuery())
          ->setViewer($viewer)
          ->needProfileImage(true)
          ->withPHIDs(array((string)$xaction->getAuthorPHID()))
          ->executeOne();

        if ($author_info) {
          $user['userName'] = $author_info->getUsername();
          $user['fullName'] = $author_info->getFullName();
          $user['realName'] = $author_info->getRealName();
          $user['profileImageURI'] = $author_info->getProfileImageURI();
        }
      }

      $detail_task = '';

      try {
        $old_value = str_replace(array('"', 'T'), '',  $xaction->getOldValue());
        $new_value = str_replace(array('"', 'T'), '',  $xaction->getNewValue());

        if ($this->getTicket($new_value) == null
          || $this->getTicket($old_value) == null) {
          $detail_task = $this->getTicket($new_value);
        } else {
          $detail_task = $this->getTicket($old_value);
        }

        $detail_task['status'] = $this->attachStatus($detail_task);
        $detail_task['priority'] = $this->attachPriority($detail_task);
        $detail_task['dateCreated'] = (int)$detail_task['dateCreated'];
        $detail_task['dateModified'] = (int)$detail_task['dateModified'];

      } catch (Throwable $th) {
        $new_value = $xaction->getNewValue();
      }

      $data[] = array(
        'id' => (int)$xaction->getID(),
        'phid' => (string)$xaction->getPHID(),
        'type' => $xaction->getTransactionType(),
        'authorPHID' => (string)$xaction->getAuthorPHID(),
        'value' => $new_value,
        'ticket' => $detail_task,
        'author' => $user,
        'objectPHID' => (string)$xaction->getObjectPHID(),
        'dateCreated' => (int)$xaction->getDateCreated(),
        'dateModified' => (int)$xaction->getDateModified(),
        'groupID' => $group_id,
      );
    }

    $results = array(
      'data' => $data,
    );

    return $this->addPagerResults($results, $pager);
  }

  public function applyDate($date_start, $date_end,
    PhabricatorApplicationTransactionQuery $query) {
    return $query->withDateCreatedBetween($date_start, $date_end);
  }

  public function applyTransactionType($transaction_types,
    PhabricatorApplicationTransactionQuery $query) {
    return $query->withTransactionType($transaction_types);
  }

  private function applyConstraints(array $constraints,
    PhabricatorApplicationTransactionQuery $query) {

    $with_phids = idx($constraints, 'phids');

    if ($with_phids === array()) {
      throw new Exception(
        pht(
          'Constraint "phids" to "transaction.search" requires nonempty list, '.
          'empty list provided.'));
    }

    if ($with_phids) {
      $query->withPHIDs($with_phids);
    }

    $with_authors = idx($constraints, 'authorPHIDs');
    if ($with_authors === array()) {
      throw new Exception(
        pht(
          'Constraint "authorPHIDs" to "transaction.search" requires '.
          'nonempty list, empty list provided.'));
    }

    if ($with_authors) {
      $query->withAuthorPHIDs($with_authors);
    }

    return $query;
  }

  private function newEdgeTransactionFields(
    PhabricatorApplicationTransaction $xaction) {

    $record = PhabricatorEdgeChangeRecord::newFromTransaction($xaction);

    $operations = array();
    foreach ($record->getAddedPHIDs() as $phid) {
      $operations[] = array(
        'operation' => 'add',
        'phid' => $phid,
      );
    }

    foreach ($record->getRemovedPHIDs() as $phid) {
      $operations[] = array(
        'operation' => 'remove',
        'phid' => $phid,
      );
    }

    return array(
      'operations' => $operations,
    );
  }


  private function loadTemplateObject(ConduitAPIRequest $request) {
    $viewer = $request->getUser();

    $object_identifier = $request->getValue('objectIdentifier');
    $object_type = 'LBYS';

    $has_identifier = ($object_identifier !== null);
    $has_type = ($object_type !== null);

    if (!$has_identifier && !$has_type) {
      throw new Exception(
        pht(
          'Calls to "transaction.search" must specify either an "objectType" '.
          'or an "objectIdentifier"'));
    } else if ($has_type && $has_identifier) {
      throw new Exception(
        pht(
          'Calls to "transaction.search" must not specify both an '.
          '"objectType" and an "objectIdentifier".'));
    }

    if ($has_type) {
      $all_types = PhabricatorPHIDType::getAllTypes();

      if (!isset($all_types[$object_type])) {
        ksort($all_types);
        throw new Exception(
          pht(
            'In call to "transaction.search", specified "objectType" ("%s") '.
            'is unknown. Valid object types are: %s.',
            $object_type,
            implode(', ', array_keys($all_types))));
      }

      $object = $all_types[$object_type]->newObject();

    } else {

      $object = id(new PhabricatorObjectQuery())
        ->setViewer($viewer)
        ->withNames(array($object_identifier))
        ->executeOne();

      if (!$object) {
        throw new Exception(
          pht(
            'In call to "transaction.search", specified "objectIdentifier" '.
            '("%s") does not exist.',
            $object_identifier));
      }
    }

    if (!($object instanceof PhabricatorApplicationTransactionInterface)) {
      throw new Exception(
        pht(
          'In call to "transaction.search", selected object (of type "%s") '.
          'does not implement "%s", so transactions can not be loaded for it.',
          get_class($object),
          'PhabricatorApplicationTransactionInterface'));
    }

    return $object;
  }

  public function getUser($conn_user, $pd_user, $task) {
    $table = $pd_user->getTableName();

    $query = queryfx_one(
      $conn_user,
      'SELECT id, phid, userName, realName FROM %T WHERE phid = %s',
      sev2table($table),
      $task['authorPHID']);

    return $query;
  }

  public function getOwner($conn_user, $pd_user, $task) {
    $table = $pd_user->getTableName();

    return queryfx_one(
      $conn_user,
      'SELECT id, phid, userName, realName FROM %T WHERE phid=%s',
      sev2table($table),
      $task['authorPHID']);
  }

  public function getAuthor($conn_user, $pd_user, $task) {
    $table = $pd_user->getTableName();
    return queryfx_one(
      $conn_user,
      'SELECT id, phid, userName, realName FROM %T WHERE phid=%s',
      sev2table($table),
      $task['ownerPHID']);
  }

  public function getTicket($new_value) {
    $pd_maniphest  = new ManiphestTask();
    $conn_maniphest = $pd_maniphest->establishConnection('r');
    $table = $pd_maniphest->getTableName();

    return queryfx_one(
      $conn_maniphest,
      'SELECT id, phid, title, points, authorPHID, '.
      'ownerPHID, status, priority, subtype, dateCreated, '.
      'dateModified FROM %T WHERE id = %s',
      sev2table($table),
      (int)str_replace(array('"', 'T'), '', $new_value));
  }

  public function attachStatus($detail_task) {
    $status_value = $detail_task['status'];
    $status_info = array(
      'value' => $status_value,
      'name' => ManiphestTaskStatus::getTaskStatusName($status_value),
      'color' => ManiphestTaskStatus::getStatusColor($status_value),
    );
    return  $status_info;
  }

  public function attachPriority($detail_task) {
    $priority_value = $detail_task['priority'];
    $priority_info = array(
      'value' => $priority_value,
      'name' => ManiphestTaskPriority::getTaskPriorityName($priority_value),
      'color' => ManiphestTaskPriority::getTaskPriorityColor($priority_value),
    );
    return $priority_info;
  }
}
