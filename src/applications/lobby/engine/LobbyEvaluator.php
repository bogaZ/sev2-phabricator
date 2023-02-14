<?php

final class LobbyEvaluator {

  public static function evaluate($data) {
    $current_user_phid = idx($data, 'user_phid');
    $current_user_data = idx($data, 'user_data', array());
    $current_state_phid = idx($data, 'state_phid');
    $current_channel = idx($data, 'channel_phid');
    $check_unavailable = idx($data, 'check_for_unavailable', false);

    $viewer = PhabricatorUser::getOmnipotentUser();

    if (PhabricatorNotificationClient::isEnabled()) {

      if ($check_unavailable) {
        self::scheduleEvaluator();
      }

      self::markAsActive($current_state_phid);

      LobbyAphlict::broadcastLobby();

      if ($current_channel) {
          LobbyAphlict::broadcastJoiningChannel($current_channel,
            $current_user_phid, $current_user_data);
      }
    }
  }

  protected static function markAsActive($phid) {
    $lobby =  id(new LobbyStateQuery())
                  ->setViewer(PhabricatorUser::getOmnipotentUser())
                  ->withPHIDs(array($phid))
                  ->executeOne();

    if ($lobby->getIsWorking()) {
      return;
    }

    $actor = $lobby->loadUser();
    $content_source = PhabricatorContentSource::newForSource(
      LobbyContentSource::SOURCECONST);

    $xactions = array();
    $xactions[] = id(new LobbyStateTransaction())
      ->setTransactionType(
        LobbyStateIsWorkingTransaction::TRANSACTIONTYPE)
      ->setNewValue(1);

    $editor = id(new LobbyStateEditor())
      ->setActor($actor)
      ->setContentSource($content_source)
      ->setContinueOnNoEffect(true);

    $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();

      $editor->applyTransactions($lobby, $xactions);

      $lobby->save();

    unset($unguarded);
  }

  protected static function scheduleEvaluator() {
    try {
      $table  = new PhabricatorWorkerActiveTask();
      $conn_r = $table->establishConnection('r');

      $result = queryfx_all(
        $conn_r,
        'SELECT COUNT(*) as count FROM %T WHERE taskClass=%s',
        $table->getTableName(),
        'LobbyEvaluatorWorker');

      $count = (int) $result[0]['count'];


      $table  = new PhabricatorWorkerArchiveTask();
      $conn_r = $table->establishConnection('r');

      $result = queryfx_all(
        $conn_r,
        'SELECT dateCreated as last_time FROM %T WHERE taskClass=%s ORDER BY dateCreated DESC limit 1',
        $table->getTableName(),
        'LobbyEvaluatorWorker');

      $last_time = (int) $result[0]['last_time'];
      $last_check = time() - $last_time;

      // Only schedule if there is no active task
      // or after 5 minutes from last check
      if ($count == 0 && $last_check > 600) {
        PhabricatorWorker::scheduleTask(
          'LobbyEvaluatorWorker',
          array(
            'time' => time(),
            'type' => 'LobbyEvaluator'
          ));
      }
    } catch (Exception $ex) {
      $err = $ex;
    } catch (Throwable $ex) {
      $err = $ex;
    }
  }

}
