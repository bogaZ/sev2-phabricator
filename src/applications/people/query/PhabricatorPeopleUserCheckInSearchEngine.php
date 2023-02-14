<?php

final class PhabricatorPeopleUserCheckInSearchEngine
  extends PhabricatorApplicationSearchEngine {

  public function getResultTypeDescription() {
    return pht('Users');
  }

  public function getApplicationClassName() {
    return 'PhabricatorPeopleApplication';
  }

  public function newQuery() {
    return id(new PhabricatorPeopleUserCheckInQuery());
  }

  protected function buildCustomSearchFields() {
    $fields = array();

    $fields[] = id(new PhabricatorSearchDateField())
      ->setKey('createdStart')
      ->setLabel(pht('Last Check In After'))
      ->setDescription(
        pht('Find user check in after a given time.'));

    $fields[] = id(new PhabricatorSearchDateField())
      ->setKey('createdEnd')
      ->setLabel(pht('Last Check In Before'))
      ->setDescription(
        pht('Find user check in before a given time.'));
    return $fields;
  }

  protected function getDefaultFieldOrder() {
    return array(
      '...',
      'createdStart',
      'createdEnd',
    );
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

  protected function getURI($path) {
    return '/people/'.$path;
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
    'data' => $data,
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

    return '';
  }


}
