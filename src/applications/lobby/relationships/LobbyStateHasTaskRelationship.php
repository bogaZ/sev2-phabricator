<?php

final class LobbyStateHasTaskRelationship
  extends LobbyRelationship {

  const RELATIONSHIPKEY = 'lobby.has-task';

  public function getEdgeConstant() {
    return LobbyHasTaskEdgeType::EDGECONST;
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
    return pht('Select current task');
  }

  public function getDialogHeaderText() {
    return pht('Current Task');
  }

  public function getDialogButtonText() {
    return pht('Set current task');
  }

  protected function newRelationshipSource() {
    return new ManiphestTaskRelationshipSource();
  }

  public function getMaximumSelectionSize() {
    return 1;
  }
}
