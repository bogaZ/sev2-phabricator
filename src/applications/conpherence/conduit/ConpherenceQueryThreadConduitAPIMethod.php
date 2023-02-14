<?php

final class ConpherenceQueryThreadConduitAPIMethod
  extends ConpherenceConduitAPIMethod {

  public function getAPIMethodName() {
    return 'conpherence.querythread';
  }

  public function getMethodDescription() {
    return pht(
      'Query for Conpherence threads for the logged in user. You can query '.
      'by IDs or PHIDs for specific Conpherence threads. Otherwise, specify '.
      'limit and offset to query the most recently updated Conpherences for '.
      'the logged in user.');
  }

  protected function defineParamTypes() {
    return array(
      'ids' => 'optional array<int>',
      'phids' => 'optional array<phids>',
      'isDeleted' => 'optional boolean',
      'limit' => 'optional int',
      'offset' => 'optional int',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $user = $request->getUser();
    $ids = $request->getValue('ids', array());
    $phids = $request->getValue('phids', array());
    $limit = $request->getValue('limit');
    $offset = $request->getValue('offset');
    $is_deleted = $request->getValue('isDeleted');

    $query = id(new ConpherenceThreadQuery())
      ->needProfileImage(true)
      ->setViewer($user);

    if ($is_deleted !== null) {
      $query->withIsDeleted((bool)$is_deleted);
    }

    $transactions = id(new ConpherenceTransactionQuery())
      ->setViewer($user)
      ->withTransactionTypes(
        array(PhabricatorCoreCreateTransaction::TRANSACTIONTYPE))
      ->setLimit($limit)
      ->setOffset($offset)
      ->execute();

    if ($ids) {
      $conpherences = $query
        ->withIDs($ids)
        ->setLimit($limit)
        ->setOffset($offset)
        ->execute();
    } else if ($phids) {
      $conpherences = $query
        ->withPHIDs($phids)
        ->setLimit($limit)
        ->setOffset($offset)
        ->execute();
    } else {
      $participation = id(new ConpherenceParticipantQuery())
        ->withParticipantPHIDs(array($user->getPHID()))
        ->setLimit($limit)
        ->setOffset($offset)
        ->execute();
      $conpherence_phids = mpull($participation, 'getConpherencePHID');
      $query->withPHIDs($conpherence_phids);
      $conpherences = $query->execute();
      $conpherences = array_select_keys($conpherences, $conpherence_phids);
    }

    $data = array();
    foreach ($conpherences as $conpherence) {
      $participant = $conpherence->getParticipants();
      if (!$participant) {
        $participant = [];
      }

      $is_joinable = false;

      if (count($participant)) {
        $map_participant = array_map(function ($object) {
        return $object->getParticipantPHID();
      }, $participant);
        $is_joinable = in_array($user->getPHID(), $map_participant, true);
      }

      $id = $conpherence->getID();
      $data[$id] = array(
        'conpherenceID' => $id,
        'conpherencePHID' => $conpherence->getPHID(),
        'conpherenceTitle' => $conpherence->getTitle(),
        'messageCount' => $conpherence->getMessageCount(),
        'conpherenceURI' => $this->getConpherenceURI($conpherence),
        'conpherenceImageURI' => $conpherence->getProfileImageURI(),
        'memberCount' => (int)count($participant),
        'isDeleted' => (int)$conpherence->getIsDeleted(),
        'isHQ' => (int)$conpherence->getIsHQ(),
        'isPublic' => (int)$conpherence->getIsPublic(),
        'isJoinable' => $is_joinable,
      );
      foreach ($transactions as $transaction) {
        if ($conpherence->getPHID() === $transaction->getObjectPHID()) {
          $data[$id]['ownerPHID'] = $transaction->getAuthorPHID();
        }
      }

      $view_policy = $conpherence->getViewPolicy();
      if ($view_policy == 'obj.conpherence.members') {
        $view_policy = 'participants';
      }

      $edit_policy = $conpherence->getEditPolicy();
      if ($edit_policy == 'obj.conpherence.members') {
        $edit_policy = 'participants';
      }

      $data[$id]['policy'] = array(
        'view' => $view_policy,
        'edit' => $edit_policy,
      );
    }

    return $data;
  }

}
