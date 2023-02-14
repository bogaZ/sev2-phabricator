<?php

final class ConpherenceThreadHasCalendarRelationship
  extends ConpherenceThreadRelationship {

  const RELATIONSHIPKEY = 'conpherence.has-calendar';

  public function getEdgeConstant() {
    return ConpherenceThreadHasCalendarEdgeType::EDGECONST;
  }

  protected function getActionName() {
    return pht('Edit Events');
  }

  protected function getActionIcon() {
    return 'fa-calendar-o';
  }

  public function canRelateObjects($src, $dst) {
    return ($dst instanceof PhabricatorCalendarEvent);
  }

  public function getDialogTitleText() {
    return pht('Select room events');
  }

  public function getDialogHeaderText() {
    return pht('Room Events');
  }

  public function getDialogButtonText() {
    return pht('Set room events');
  }

  protected function newRelationshipSource() {
    return new CalendarRelationshipSource();
  }
}
