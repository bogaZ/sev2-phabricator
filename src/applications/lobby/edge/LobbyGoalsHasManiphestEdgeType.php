<?php

final class LobbyGoalsHasManiphestEdgeType extends PhabricatorEdgeType {

  const EDGECONST = 3010;

  public function getInverseEdgeConstant() {
    return ManiphestTaskHasLobbyThreadEdgeType::EDGECONST;
  }

  public function shouldWriteInverseTransactions() {
    return true;
  }

  public function getConduitKey() {
    return 'goals.attached-maniphest';
  }

  public function getConduitName() {
    return pht('Goals Has Room');
  }

  public function getConduitDescription() {
    return pht('The goals is attached to the maniphest.');
  }

}
