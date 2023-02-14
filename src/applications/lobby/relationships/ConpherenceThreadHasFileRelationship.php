<?php

final class ConpherenceThreadHasFileRelationship
  extends ConpherenceThreadRelationship {

  const RELATIONSHIPKEY = 'conpherence.has-file';

  public function getEdgeConstant() {
    return ConpherenceThreadHasFileEdgeType::EDGECONST;
  }

  protected function getActionName() {
    return pht('Edit Files');
  }

  protected function getActionIcon() {
    return 'fa-paperclip';
  }

  public function canRelateObjects($src, $dst) {
    return ($dst instanceof PhabricatorFile);
  }

  public function getDialogTitleText() {
    return pht('Select room files');
  }

  public function getDialogHeaderText() {
    return pht('Room Files');
  }

  public function getDialogButtonText() {
    return pht('Set room files');
  }

  protected function newRelationshipSource() {
    return new FileRelationshipSource();
  }
}
