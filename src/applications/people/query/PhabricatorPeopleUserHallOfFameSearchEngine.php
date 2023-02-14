<?php

final class PhabricatorPeopleUserHallOfFameSearchEngine
  extends PhabricatorApplicationSearchEngine {

  public function getResultTypeDescription() {
    return pht('Hall Of Fame');
  }

  public function getApplicationClassName() {
    return 'PhabricatorPeopleApplication';
  }

  public function newQuery() {
    $query = new PhabricatorPeopleQuery();

    // NOTE: If the viewer isn't an administrator, always restrict the query to
    // related records. This echoes the policy logic of these logs. This is
    // mostly a performance optimization, to prevent us from having to pull
    // large numbers of logs that the user will not be able to see and filter
    // them in-process.
    $viewer = $this->requireViewer();

    return $query;
  }

  protected function buildQueryFromParameters(array $map) {
    $query = $this->newQuery();

    if ($map['createdStart']) {
      $query->withDateCreatedAfter($map['createdStart']);
    }

    if ($map['createdEnd']) {
      $query->withDateCreatedBefore($map['createdEnd']);
    }

    return $query;
  }


  protected function buildCustomSearchFields() {
    $fields = array();
    $fields[] = id(new PhabricatorSearchDateField())
      ->setKey('createdStart')
      ->setLabel(pht('Reward Date Range After'))
      ->setDescription(
        pht('Find user hall of fame after a given time.'));

    $fields[] = id(new PhabricatorSearchDateField())
      ->setKey('createdEnd')
      ->setLabel(pht('Reward Date Range Before'))
      ->setDescription(
        pht('Find user hall of fame before a given time.'));

    return $fields;
  }

  protected function getDefaultFieldOrder() {
    return array(
      '...',
      'isResult',
      'createdStart',
      'createdEnd',
    );
  }


  protected function getURI($path) {
    return '/people/halloffame/'.$path;
  }

  protected function getBuiltinQueryNames() {
    $names = array(
      'all' => pht('All'),
    );

    return $names;
  }

  public function buildSavedQueryFromBuiltin($query_key) {
    $query = $this->newSavedQuery();
    $query->setQueryKey($query_key);
    $viewer = $this->requireViewer();
    switch ($query_key) {
      case 'all':
        return $query;
    }
    return parent::buildSavedQueryFromBuiltin($query_key);
  }

/* -(  Application Search  )------------------------------------------------- */



public function getSearchFieldsForConduit() {
  $standard_fields = $this->buildSearchFields();

  $fields = array();
  foreach ($standard_fields as $field_key => $field) {
    $conduit_key = $field->getConduitKey();

    if (isset($fields[$conduit_key])) {
      $other = $fields[$conduit_key];
      $other_key = $other->getKey();

      throw new Exception(
        pht(
          'SearchFields "%s" (of class "%s") and "%s" (of class "%s") both '.
          'define the same Conduit key ("%s"). Keys must be unique.',
          $field_key,
          get_class($field),
          $other_key,
          get_class($other),
          $conduit_key));
    }

    $fields[$conduit_key] = $field;
  }

  // These are handled separately for Conduit, so don't show them as
  // supported.
  unset($fields['order']);
  unset($fields['limit']);

  $viewer = $this->requireViewer();
  foreach ($fields as $key => $field) {
    $field->setViewer($viewer);
  }

  return $fields;
}

public function buildConduitResponse(
  ConduitAPIRequest $request,
  ConduitAPIMethod $method) {
  $viewer = $this->requireViewer();

  $query_key = $request->getValue('queryKey');
  if (!strlen($query_key)) {
    $saved_query = new PhabricatorSavedQuery();
  } else if ($this->isBuiltinQuery($query_key)) {
    $saved_query = $this->buildSavedQueryFromBuiltin($query_key);
  } else {
    $saved_query = id(new PhabricatorSavedQueryQuery())
      ->setViewer($viewer)
      ->withQueryKeys(array($query_key))
      ->executeOne();
    if (!$saved_query) {
      throw new Exception(
        pht(
          'Query key "%s" does not correspond to a valid query.',
          $query_key));
    }
  }

  $constraints = $request->getValue('constraints', array());
  if (!is_array($constraints)) {
    throw new Exception(
      pht(
        'Parameter "constraints" must be a map of constraints, got "%s".',
        phutil_describe_type($constraints)));
  }

  $fields = $this->getSearchFieldsForConduit();

  foreach ($fields as $key => $field) {
    if (!$field->getConduitParameterType()) {
      unset($fields[$key]);
    }
  }

  $valid_constraints = array();
  foreach ($fields as $field) {
    foreach ($field->getValidConstraintKeys() as $key) {
      $valid_constraints[$key] = true;
    }
  }

  foreach ($constraints as $key => $constraint) {
    if (empty($valid_constraints[$key])) {
      throw new Exception(
        pht(
          'Constraint "%s" is not a valid constraint for this query.',
          $key));
    }
  }

  foreach ($fields as $field) {
    if (!$field->getValueExistsInConduitRequest($constraints)) {
      continue;
    }

    $value = $field->readValueFromConduitRequest(
      $constraints,
      $request->getIsStrictlyTyped());
    $saved_query->setParameter($field->getKey(), $value);
  }

  // NOTE: Currently, when running an ad-hoc query we never persist it into
  // a saved query. We might want to add an option to do this in the future
  // (for example, to enable a CLI-to-Web workflow where user can view more
  // details about results by following a link), but have no use cases for
  // it today. If we do identify a use case, we could save the query here.

  $query = $this->buildQueryFromSavedQuery($saved_query);
  $pager = $this->newPagerForSavedQuery($saved_query);

  $this->setQueryOrderForConduit($query, $request);
  $this->setPagerLimitForConduit($pager, $request);
  $this->setPagerOffsetsForConduit($pager, $request);

  $objects = $this->executeQuery($query, $pager);

  $data = array();
  if ($objects) {
    $field_extensions = $this->getConduitFieldExtensions();
    $extension_data = array();
    foreach ($field_extensions as $key => $extension) {
      $extension_data[$key] = $extension->loadExtensionConduitData($objects);
    }

    foreach ($objects as $object) {
      try {
        $field_map = $this->getObjectWireFieldsForConduit(
          $object,
          $field_extensions,
          $extension_data);
      } catch (PhabricatorPolicyException $ex) {
        continue;
      }

      // If this is empty, we still want to emit a JSON object, not a
      // JSON list.


      $id = (int)$object->getID();

      $data[] = array(
        'id' => $id,
        'phid' => $field_map['phid'],
        'dateCreated' => $field_map['dateCreated'],
      );
    }
  }

  return array(

    'maps' => $method->getQueryMaps($query),
    'query' => array(
      // This may be `null` if we have not saved the query.
      'queryKey' => $saved_query->getQueryKey(),
    ),
    'cursor' => array(
      'limit' => $pager->getPageSize(),
      'after' => $pager->getNextPageID(),
      'before' => $pager->getPrevPageID(),
      'order' => $request->getValue('order'),
    ),
  );
}

public function getAllConduitFieldSpecifications() {
  $extensions = $this->getConduitFieldExtensions();
  $object = $this->newQuery()->newResultObject();

  $map = array();
  foreach ($extensions as $extension) {
    $specifications = $extension->getFieldSpecificationsForConduit($object);
    foreach ($specifications as $specification) {
      $key = $specification->getKey();
      if (isset($map[$key])) {
        throw new Exception(
          pht(
            'Two field specifications share the same key ("%s"). Each '.
            'specification must have a unique key.',
            $key));
      }
      $map[$key] = $specification;
    }
  }

  return $map;
}

private function getEngineExtensions() {
  $extensions = PhabricatorSearchEngineExtension::getAllEnabledExtensions();

  foreach ($extensions as $key => $extension) {
    $extension
      ->setViewer($this->requireViewer())
      ->setSearchEngine($this);
  }

  $object = $this->newResultObject();
  foreach ($extensions as $key => $extension) {
    if (!$extension->supportsObject($object)) {
      unset($extensions[$key]);
    }
  }

  return $extensions;
}


private function getConduitFieldExtensions() {
  $extensions = $this->getEngineExtensions();
  $object = $this->newResultObject();

  foreach ($extensions as $key => $extension) {
    if (!$extension->getFieldSpecificationsForConduit($object)) {
      unset($extensions[$key]);
    }
  }

  return $extensions;
}

private function setQueryOrderForConduit($query, ConduitAPIRequest $request) {
  $order = $request->getValue('order');
  if ($order === null) {
    return;
  }

  if (is_scalar($order)) {
    $query->setOrder($order);
  } else {
    $query->setOrderVector($order);
  }
}

private function setPagerLimitForConduit($pager, ConduitAPIRequest $request) {
  $limit = $request->getValue('limit');

  // If there's no limit specified and the query uses a weird huge page
  // size, just leave it at the default gigantic page size. Otherwise,
  // make sure it's between 1 and 100, inclusive.

  if ($limit === null) {
    if ($pager->getPageSize() >= 0xFFFF) {
      return;
    } else {
      $limit = 100;
    }
  }

  if ($limit > 100) {
    throw new Exception(
      pht(
        'Maximum page size for Conduit API method calls is 100, but '.
        'this call specified %s.',
        $limit));
  }

  if ($limit < 1) {
    throw new Exception(
      pht(
        'Minimum page size for API searches is 1, but this call '.
        'specified %s.',
        $limit));
  }

  $pager->setPageSize($limit);
}

private function setPagerOffsetsForConduit(
  $pager,
  ConduitAPIRequest $request) {
  $before_id = $request->getValue('before');
  if ($before_id !== null) {
    $pager->setBeforeID($before_id);
  }

  $after_id = $request->getValue('after');
  if ($after_id !== null) {
    $pager->setAfterID($after_id);
  }
}

public function getAllUser($viewer) {
  $all_user = (new PhabricatorPeopleQuery($viewer))
  ->setViewer($viewer)
  ->needPrimaryEmail(true)
  ->needProfileImage(true)
  ->execute();

  yield $all_user;
}

protected function getObjectWireFieldsForConduit(
  $object,
  array $field_extensions,
  array $extension_data) {
  $fields = array();
  foreach ($field_extensions as $key => $extension) {
    $data = idx($extension_data, $key, array());
    $fields += $extension->getFieldValuesForConduit($object, $data);
  }
  return $fields;
}

/* -( End Application Search  )---------------- */

protected function renderResultList(
  array $users,
  PhabricatorSavedQuery $query,
  array $handles) {

      $viewer = $this->requireViewer();

      $table  = new ManiphestTask();
      $conn_r = $table->establishConnection('r');

      $project = new PhabricatorProject();
      $conn_rp = $project->establishConnection('r');
      $get = queryfx_one(
        $conn_rp,
        'SELECT phid FROM %T'.
        'WHERE name = %s',
        sev2table($project->getTableName()),
        'Approved By Principal');

      $edge_query = id(new PhabricatorEdgeQuery())
      ->withSourcePHIDs(array($get['phid']))
      ->withEdgeTypes(
        array(
          PhabricatorProjectProjectHasObjectEdgeType::EDGECONST,
        ))
        ->execute();

        $task_project = array();
        $task_phids = array();

        foreach ($edge_query[$get['phid']] as $key => $phid) {
          array_push($task_project, $phid);
        }
        foreach ($task_project[0] as $key => $phid) {
          array_push($task_phids, $key);
        }

        $maniphest = new ManiphestTask();
        $conn_mt = $maniphest->establishConnection('r');
        $get_man = queryfx_all(
          $conn_mt,
          'SELECT ownerPHID, count(points), sum(points) as points FROM %T'.
          'WHERE phid IN (%Ls) AND status = %s AND progress = %d
          AND DATE(FROM_UNIXTIME(closedEpoch)) = curDate()
          GROUP BY ownerPHID
          ORDER BY points desc',
          sev2table($maniphest->getTableName()),
          $task_phids,
          'resolved',
          100);

        $usere = id(new PhabricatorPeopleQuery())
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->execute();

        $task_all = queryfx_all(
          $conn_mt,
          'SELECT ownerPHID, points, id FROM %T'.
          'WHERE phid IN (%Ls) AND status = %s AND progress = %d
          AND DATE(FROM_UNIXTIME(closedEpoch)) = curDate()',
          sev2table($maniphest->getTableName()),
          $task_phids,
          'resolved',
          100);

      foreach ($get_man as $key => $value) {
        $get_man[$key]['ticket'] = array();
        foreach ($task_all as $key1 => $value1) {
          if ($value1['ownerPHID'] == $value['ownerPHID']) {
           array_push($get_man[$key]['ticket'],
           phutil_tag('a', array('href' => '/T'.$value1['id']),
           array(pht('T%s ', $value1['id']))));
          }
        }
      }

      foreach ($get_man as $key => $value) {
        foreach ($usere as $key1 => $value1) {
          $phid = $value1->getPHID();

          if ($phid == $value['ownerPHID']) {
            $get_man[$key]['ownerPHID'] =
            phutil_tag('a', array('href' => '/p/'.$value1->getUserName().'/'),
            array($value1->getRealName()));
           }
        }
      }

    $rows = array();
        $headers = array(
          pht('User'),
          pht('Tickets'),
          pht('Story Points'),
          pht('Tickets Reference'),
        );
    $column_classes = array(
          'pri',
          'left',
          'left',
          ' ',
        );

    $table = id(new AphrontTableView($get_man))
      ->setNoDataString(pht('No users match the query.'))
      ->setHeaders($headers)
      ->setColumnClasses($column_classes);

    $result = new PhabricatorApplicationSearchResultView();
    $result->setTable($table);
    return $result;
    }

  }
