<?php

final class ConpherenceThreadHasStickitRelationship
  extends ConpherenceThreadRelationship {

  const RELATIONSHIPKEY = 'conpherence.has-stickit';

  public function getEdgeConstant() {
    return ConpherenceThreadHasStickitEdgeType::EDGECONST;
  }

  protected function getActionName() {
    return pht('Edit Stickit');
  }

  protected function getActionIcon() {
    return 'fa-thumb-tack';
  }

  public function canRelateObjects($src, $dst) {
    return ($dst instanceof LobbyStickit);
  }

  public function getDialogTitleText() {
    return pht('Select room stickit');
  }

  public function getDialogHeaderText() {
    return pht('Room Stickit');
  }

  public function getDialogButtonText() {
    return pht('Set room stickit');
  }

  protected function newRelationshipSource() {
    return new StickitRelationshipSource();
  }
}
