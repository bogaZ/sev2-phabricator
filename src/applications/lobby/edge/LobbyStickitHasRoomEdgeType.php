<?php

final class LobbyStickitHasRoomEdgeType extends PhabricatorEdgeType {

  const EDGECONST = 3005;

  public function getInverseEdgeConstant() {
    return ConpherenceThreadHasStickitEdgeType::EDGECONST;
  }

  public function shouldWriteInverseTransactions() {
    return true;
  }

  public function getConduitKey() {
    return 'stickit.attached-conpherence';
  }

  public function getConduitName() {
    return pht('Stickit Has Room');
  }

  public function getConduitDescription() {
    return pht('The stickit is attached to the destination room.');
  }

}
