<?php

final class PhabricatorCalendarEventHasConpherenceEdgeType extends PhabricatorEdgeType {

  const EDGECONST = 3004;

  public function getInverseEdgeConstant() {
    return ConpherenceThreadHasCalendarEdgeType::EDGECONST;
  }

  public function shouldWriteInverseTransactions() {
    return true;
  }

  public function getConduitKey() {
    return 'event.attached-conpherence';
  }

  public function getConduitName() {
    return pht('Event Has Room');
  }

  public function getConduitDescription() {
    return pht('The event is attached to the destination room.');
  }

}
