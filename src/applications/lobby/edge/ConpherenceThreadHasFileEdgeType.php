<?php

final class ConpherenceThreadHasFileEdgeType extends PhabricatorEdgeType {

  const EDGECONST = 3001;

  public function getInverseEdgeConstant() {
    return PhabricatorFileHasConpherenceEdgeType::EDGECONST;
  }

  public function shouldWriteInverseTransactions() {
    return false;
  }

  public function getConduitKey() {
    return 'conpherence.files';
  }

  public function getConduitName() {
    return pht('Room Has File');
  }

  public function getConduitDescription() {
    return pht('The room is associated with the destination file(s).');
  }

  public function getTransactionAddString(
    $actor,
    $add_count,
    $add_edges) {

    return pht(
      '%s added %s file(s): %s.',
      $actor,
      $add_count,
      $add_edges);
  }

  public function getTransactionRemoveString(
    $actor,
    $rem_count,
    $rem_edges) {

    return pht(
      '%s removed %s file(s): %s.',
      $actor,
      $rem_count,
      $rem_edges);
  }

  public function getTransactionEditString(
    $actor,
    $total_count,
    $add_count,
    $add_edges,
    $rem_count,
    $rem_edges) {

    return pht(
      '%s edited %s file(s), added %s: %s; removed %s: %s.',
      $actor,
      $total_count,
      $add_count,
      $add_edges,
      $rem_count,
      $rem_edges);
  }

  public function getFeedAddString(
    $actor,
    $object,
    $add_count,
    $add_edges) {

    return pht(
      '%s added %s file(s) to %s: %s.',
      $actor,
      $add_count,
      $object,
      $add_edges);
  }

  public function getFeedRemoveString(
    $actor,
    $object,
    $rem_count,
    $rem_edges) {

    return pht(
      '%s removed %s file(s) from %s: %s.',
      $actor,
      $rem_count,
      $object,
      $rem_edges);
  }

  public function getFeedEditString(
    $actor,
    $object,
    $total_count,
    $add_count,
    $add_edges,
    $rem_count,
    $rem_edges) {

    return pht(
      '%s edited %s file(s) for %s, added %s: %s; removed %s: %s.',
      $actor,
      $total_count,
      $object,
      $add_count,
      $add_edges,
      $rem_count,
      $rem_edges);
  }

}
