<?php

final class ManiphestTaskHasLobbyEdgeType extends PhabricatorEdgeType {

  const EDGECONST = 99;

  public function getInverseEdgeConstant() {
    return LobbyHasTaskEdgeType::EDGECONST;
  }

  public function shouldWriteInverseTransactions() {
    return true;
  }

  public function getConduitKey() {
    return 'task.lobby';
  }

  public function getConduitName() {
    return pht('Task Has Lobby');
  }

  public function getConduitDescription() {
    return pht('The source task is associated with the destination lobby.');
  }

  public function getTransactionAddString(
    $actor,
    $add_count,
    $add_edges) {

    return pht(
      '%s started new task.',
      $actor);
  }

  public function getTransactionRemoveString(
    $actor,
    $rem_count,
    $rem_edges) {

    return pht(
      '%s stopped his current task.',
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
      '%s edited his current task(s).',
      $actor);
  }


    public function getFeedAddString(
      $actor,
      $object,
      $add_count,
      $add_edges) {

      return pht(
        '%s started %s.',
        $actor,
        $object);
    }

    public function getFeedRemoveString(
      $actor,
      $object,
      $rem_count,
      $rem_edges) {

      return pht(
        '%s stopped %s.',
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
        '%s modified his current task(s) : %s.',
        $actor,
        $object);
    }
}
