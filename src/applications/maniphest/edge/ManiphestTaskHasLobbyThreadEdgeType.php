<?php

final class ManiphestTaskHasLobbyThreadEdgeType extends PhabricatorEdgeType {

  const EDGECONST = 101;

  public function getInverseEdgeConstant() {
    return LobbyGoalsHasManiphestEdgeType::EDGECONST;
  }

  public function shouldWriteInverseTransactions() {
    return true;
  }

  public function getConduitKey() {
    return 'task.lobby';
  }

  public function getConduitName() {
    return pht('Task Has lobby');
  }

  public function getConduitDescription() {
    return pht('The source task is associated with the destination lobby.');
  }

  public function getTransactionAddString(
    $actor,
    $add_count,
    $add_edges) {

    return pht(
      '%s added new task.',
      $actor);
  }

  public function getTransactionRemoveString(
    $actor,
    $rem_count,
    $rem_edges) {

    return pht(
      '%s remove task.',
      $actor);
  }

  public function getTransactionEditString(
    $actor,
    $total_count,
    $add_count,
    $add_edges,
    $rem_count,
    $rem_edges) {

    return pht(
      '%s edited task(s).',
      $actor);
  }


  public function getFeedAddString(
    $actor,
    $object,
    $add_count,
    $add_edges) {

    return pht(
      '%s added %s.',
      $actor,
      $object);
  }

  public function getFeedRemoveString(
    $actor,
    $object,
    $rem_count,
    $rem_edges) {

    return pht(
      '%s removed %s.',
      $actor,
      $object);
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
      '%s modified task(s) : %s.',
      $actor,
      $object);
  }
}
