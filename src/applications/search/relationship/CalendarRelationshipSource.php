<?php

final class CalendarRelationshipSource
  extends PhabricatorObjectRelationshipSource {

  public function isEnabledForObject($object) {
    $viewer = $this->getViewer();

    return PhabricatorApplication::isClassInstalledForViewer(
      'PhabricatorLobbyApplication',
      $viewer);
  }

  public function getResultPHIDTypes() {
    return array(
      PhabricatorCalendarEventPHIDType::TYPECONST,
    );
  }

  public function getFilters() {
    return array(
      'created' => pht('Created By Me'),
      'all' => pht('All Objects'),
    );
  }

}
