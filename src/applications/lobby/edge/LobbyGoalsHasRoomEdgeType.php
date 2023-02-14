<?php

final class LobbyGoalsHasRoomEdgeType extends PhabricatorEdgeType {

  const EDGECONST = 3007;

  public function getInverseEdgeConstant() {
    return ConpherenceThreadHasGoalsEdgeType::EDGECONST;
  }

  public function shouldWriteInverseTransactions() {
    return true;
  }

  public function getConduitKey() {
    return 'goals.attached-conpherence';
  }

  public function getConduitName() {
    return pht('Goals Has Room');
  }

  public function getConduitDescription() {
    return pht('The goals is attached to the destination room.');
  }

}
