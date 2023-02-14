<?php

final class LobbyEvaluatorWorker
  extends PhabricatorWorker {

  public function getMaximumRetryCount() {
    return 3;
  }

  protected function doWork() {
    $data = $this->getTaskData();

    $unavailable_states = idx($data, 'unavailable_states', array());
    $task_time = idx($data, 'time');

    if (PhabricatorNotificationClient::isEnabled()) {

      $servers = PhabricatorNotificationServerRef::getEnabledAdminServers();
      $active_clients = array();

      foreach($servers as $server) {
        $response = $server->loadActiveClients();

        if (!empty($response) && isset($response['active_clients'])) {
          foreach($response['active_clients'] as $members) {
            $active_clients = array_merge($active_clients,
                  $members);
          }
        }
      }
      $active_clients = array_keys($active_clients);

      $valid_clients = array();
      foreach($active_clients as $active_client) {
        if (strpos($active_client, 'PHID') === false) {
          continue;
        }

        $valid_clients[] = $active_client;
      }

      // Mark unavailable person
      $unavailable_query =  id(new LobbyStateQuery())
                    ->setViewer(PhabricatorUser::getOmnipotentUser());
      if (!empty($valid_clients)) {
          $unavailable_query->withoutOwnerPHIDs($valid_clients);
      }

      $unavailable_states = $unavailable_query->execute();

      $this->markLeavingWork($unavailable_states);
      $this->checkInactiveLobbyist();

      LobbyAphlict::broadcastLobby();

      $this->purgeOldPublishData();
      $this->purgeOldPublish();
    }
  }

  private function purgeOldPublishData() {
    try {
      $table  = new PhabricatorWorkerArchiveTask();
      $conn_r = $table->establishConnection('r');

      $result = queryfx_all(
        $conn_r,
        'SELECT dataID FROM %T WHERE taskClass=%s ORDER BY dateCreated DESC limit 1',
        $table->getTableName(),
        'PhabricatorApplicationTransactionPublishWorker');

      // Get latest id
      $latest_id = (int) $result[0]['dataID'];

      // Drop all task data lower than latest archived id, for LBYS
      $table  = new PhabricatorWorkerTaskData();
      $conn_r = $table->establishConnection('w');

      $result = queryfx_all(
        $conn_r,
        'DELETE FROM %T WHERE id < %d AND (data LIKE %s OR data LIKE %s)',
        $table->getTableName(),
        $latest_id,
        '%LBYS%',
        '%LobbyEvaluator%');
    } catch (Exception $ex) {
      $err = $ex;
    } catch (Throwable $ex) {
      $err = $ex;
    }

  }

  private function purgeOldPublish() {
    try {
      // Delete all archived publish activities
      $table  = new PhabricatorWorkerArchiveTask();
      $conn_r = $table->establishConnection('w');

      $result = queryfx_all(
        $conn_r,
        'DELETE FROM %T WHERE taskClass=%s OR taskClass=%s',
        $table->getTableName(),
        'PhabricatorApplicationTransactionPublishWorker',
        'LobbyEvaluatorWorker');
    } catch (Exception $ex) {
      $err = $ex;
    } catch (Throwable $ex) {
      $err = $ex;
    }

  }

  private function checkInactiveLobbyist() {
    $hour_ago = (PhabricatorTime::getNow() - 3600);
    $inactive_lobbyist = id(new LobbyStateQuery())
                  ->setViewer(PhabricatorUser::getOmnipotentUser())
                  ->withDateModifiedBefore($hour_ago)
                  ->withStatusExcluded(array(LobbyState::STATUS_IN_CHANNEL))
                  ->execute();

    $this->markLeavingWork($inactive_lobbyist);
  }

  private function markLeavingWork($states = array()) {
    try {
      $content_source = PhabricatorContentSource::newForSource(
        LobbyContentSource::SOURCECONST);
      foreach($states as $lobby) {
        $actor = $lobby->loadUser();
        $previous_status = $lobby->getStatus();
        $previous_channel = $lobby->getCurrentChannel();

        $xactions = array();

        $xactions[] = id(new LobbyStateTransaction())
          ->setTransactionType(
            LobbyStateIsWorkingTransaction::TRANSACTIONTYPE)
          ->setNewValue(0);
        $xactions[] = id(new LobbyStateTransaction())
          ->setTransactionType(
            LobbyStateStatusTransaction::TRANSACTIONTYPE)
          ->setNewValue(LobbyState::STATUS_IN_LOBBY);

        $editor = id(new LobbyStateEditor())
          ->setActor($actor)
          ->setContentSource($content_source)
          ->setContinueOnNoEffect(true);

        $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();

          $editor->applyTransactions($lobby, $xactions);

          $lobby->save();

        unset($unguarded);

        if ($previous_channel
          && $previous_status == LobbyState::STATUS_IN_CHANNEL) {
          // Mark leaving
          LobbyAphlict::broadcastLeavingChannel($previous_channel,
            $actor->getPHID(), array(
              'name' => $actor->getFullName(),
              'image_uri' => $actor->getProfileImageURI(),
            ));
        }
      }
    } catch (Exception $ex) {
      $err = $ex;
    } catch (Throwable $ex) {
      $err = $ex;
    }
  }

}
