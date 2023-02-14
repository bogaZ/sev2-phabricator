<?php

final class PhabricatorFileHasConpherenceEdgeType extends PhabricatorEdgeType {

  const EDGECONST = 3002;

  public function getInverseEdgeConstant() {
    return ConpherenceThreadHasFileEdgeType::EDGECONST;
  }

  public function shouldWriteInverseTransactions() {
    return true;
  }

  public function getConduitKey() {
    return 'file.attached-conpherence';
  }

  public function getConduitName() {
    return pht('File Has Room');
  }

  public function getConduitDescription() {
    return pht('The source file is attached to the destination room.');
  }

}
