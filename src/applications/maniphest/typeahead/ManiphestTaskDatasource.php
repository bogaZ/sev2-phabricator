<?php

final class ManiphestTaskDatasource
  extends PhabricatorTypeaheadDatasource {

  public function getBrowseTitle() {
    return pht('Browse Ticket');
  }

  public function getPlaceholderText() {
    return pht('Type a ticket name...');
  }

  public function getDatasourceApplicationClass() {
    return 'PhabricatorManiphestApplication';
  }

  public function loadResults() {
    $viewer = $this->getViewer();

    $query = id(new ManiphestTaskQuery())
      ->setOrderVector(array('id'));

    if ($this->getPhase() == self::PHASE_PREFIX) {
      $prefix = $this->getPrefixQuery();
      $query->withTitlePrefixes(array($prefix));
    } else {
      $tokens = $this->getTokens();
      if ($tokens) {
        $query->withTitleLike(array_pop($tokens));
      }
    }

    $tasks = $this->executeQuery($query);
    $tasks = mpull($tasks, null, 'getPHID');

    $is_browse = $this->getIsBrowse();
    if ($is_browse && $tasks) {
      $phids = mpull($tasks, 'getPHID');
      $handles = id(new PhabricatorHandleQuery())
        ->setViewer($viewer)
        ->withPHIDs($phids)
        ->execute();
    }

    $results = array();
    foreach ($tasks as $task) {
      $phid = $task->getPHID();

      $task_result = id(new PhabricatorTypeaheadResult())
        ->setName(pht('T%s: %s', $task->getID(), $task->getTitle()))
        ->setPriorityString($task->getTitle())
        ->setURI($task->getURI())
        ->setPHID($phid)
        ->setPriorityType('task');

      $results[] = $task_result;
    }

    return $results;
  }

}
