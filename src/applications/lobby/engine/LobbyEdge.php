<?php

final class LobbyEdge {
  public function setViewer($viewer) {
    $this->viewer = $viewer;
    return $this;
  }

  public function getViewer() {
    return $this->viewer;
  }

  public function setThread($thread) {
    $this->thread = $thread;
    return $this;
  }

  public function getThread() {
    return $this->thread;
  }

  public function getStickits() {
    $items = array();
    $stickit_phids = PhabricatorEdgeQuery::loadDestinationPHIDs(
      $this->getThread()->getPHID(),
      ConpherenceThreadHasStickitEdgeType::EDGECONST);

    if (!empty($stickit_phids)) {
      $items = id(new LobbyStickitQuery())
              ->setViewer($this->getViewer())
              ->withPHIDs($stickit_phids)
              ->needOwner(true)
              ->execute();
    }

    return $items;
  }

  public function getGoals() {
    $items = array();
    $stickit_phids = PhabricatorEdgeQuery::loadDestinationPHIDs(
      $this->getThread()->getPHID(),
      ConpherenceThreadHasGoalsEdgeType::EDGECONST);

    if (!empty($stickit_phids)) {
      $items = id(new LobbyStickitQuery())
              ->setViewer($this->getViewer())
              ->withPHIDs($stickit_phids)
              ->needOwner(true)
              ->withDateCreatedAfter(strtotime('today'))
              ->execute();
    }

    return $items;
  }

  public function getTasks() {
    $items = array();
    $task_phids = PhabricatorEdgeQuery::loadDestinationPHIDs(
      $this->getThread()->getPHID(),
      ConpherenceThreadHasTaskEdgeType::EDGECONST);

    if (!empty($task_phids)) {
      $items = id(new ManiphestTaskQuery())
              ->setViewer($this->getViewer())
              ->withPHIDs($task_phids)
              ->needProjectPHIDs(true)
              ->execute();
    }

    return $items;
  }

  public function getCalendars() {
    $items = array();
    $event_phids = PhabricatorEdgeQuery::loadDestinationPHIDs(
      $this->getThread()->getPHID(),
      ConpherenceThreadHasCalendarEdgeType::EDGECONST);

    if (!empty($event_phids)) {
      $items = id(new PhabricatorCalendarEventQuery())
              ->setViewer($this->getViewer())
              ->withPHIDs($event_phids)
              ->needRSVPs(array($this->getViewer()->getPHID()))
              ->needHost(true)
              ->execute();
    }

    return $items;
  }

  public function getFiles() {
    $items = array();
    $files_phids = PhabricatorEdgeQuery::loadDestinationPHIDs(
      $this->getThread()->getPHID(),
      ConpherenceThreadHasFileEdgeType::EDGECONST);

    if (!empty($files_phids)) {
      $items = id(new PhabricatorFileQuery())
              ->setViewer($this->getViewer())
              ->withPHIDs($files_phids)
              ->needAuthor(true)
              ->execute();
    }

    return $items;
  }
}
