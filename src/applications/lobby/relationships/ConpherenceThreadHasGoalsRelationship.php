<?php

final class ConpherenceThreadHasGoalsRelationship
  extends ConpherenceThreadRelationship {

  const RELATIONSHIPKEY = 'conpherence.has-goals';

  public function getEdgeConstant() {
    return ConpherenceThreadHasGoalsEdgeType::EDGECONST;
  }

  protected function getActionName() {
    return pht('Edit Goals');
  }

  protected function getActionIcon() {
    return 'fa-thumb-tack';
  }

  public function canRelateObjects($src, $dst) {
    return ($dst instanceof LobbyStickit);
  }

  public function getDialogTitleText() {
    return pht('Select room goals');
  }

  public function getDialogHeaderText() {
    return pht('Room Goals');
  }

  public function getDialogButtonText() {
    return pht('Set room goals');
  }

  protected function newRelationshipSource() {
    return new StickitRelationshipSource();
  }
}
