<?php

final class LobbyEventResultListView extends AphrontView {

  private $baseUri;
  private $noDataString;
  private $events;
  private $user;

  public function setBaseUri($base_uri) {
    $this->baseUri = $base_uri;
    return $this;
  }

  public function setUser(PhabricatorUser $user) {
    $this->user = $user;
    return $this;
  }

  protected function getUser() {
    return $this->user;
  }

  public function setEvents(array $events) {
    $this->events = $events;
    return $this;
  }

  public function getEvents() {
    return $this->events;
  }

  public function render() {
    $viewer = $this->getUser();
    $events = $this->getEvents();
    assert_instances_of($events, 'PhabricatorCalendarEvent');
    $list = new PHUIObjectItemListView();

    foreach ($events as $event) {
      if ($event->getIsGhostEvent()) {
        $monogram = $event->getParentEvent()->getMonogram();
        $index = $event->getSequenceIndex();
        $monogram = "{$monogram}/{$index}";
      } else {
        $monogram = $event->getMonogram();
      }

      $item = id(new PHUIObjectItemView())
        ->setUser($viewer)
        ->setObject($event)
        ->setObjectName($monogram)
        ->setHeader($event->getName())
        ->setHref($event->getURI());

      $item->addAttribute($event->renderEventDate($viewer, false));

      if ($event->getIsCancelled()) {
        $item->setDisabled(true);
      }

      $status_icon = $event->getDisplayIcon($viewer);
      $status_color = $event->getDisplayIconColor($viewer);
      $status_label = $event->getDisplayIconLabel($viewer);

      $item->setStatusIcon("{$status_icon} {$status_color}", $status_label);

      $host = pht(
        'Hosted by %s',
        $viewer->renderHandle($event->getHostPHID()));
      $item->addByline($host);

      $list->addItem($item);
    }

    return $list;
  }

}
