<?php

final class ConpherenceThreadHasTaskRelationship
  extends ConpherenceThreadRelationship {

  const RELATIONSHIPKEY = 'conpherence.has-task';

  public function getEdgeConstant() {
    return ConpherenceThreadHasTaskEdgeType::EDGECONST;
  }

  protected function getActionName() {
    return pht('Edit Tasks');
  }

  protected function getActionIcon() {
    return 'fa-anchor';
  }

  public function canRelateObjects($src, $dst) {
    return ($dst instanceof ManiphestTask && $dst->getStatus() != 'resolved');
  }

  public function getDialogTitleText() {
    return pht('Select room tasks');
  }

  public function getDialogHeaderText() {
    return pht('Room Tasks');
  }

  public function getDialogButtonText() {
    return pht('Set room tasks');
  }

  protected function newRelationshipSource() {
    return new ManiphestTaskRelationshipSource();
  }

  public function getMaximumSelectionSize() {
    return 10;
  }
}
