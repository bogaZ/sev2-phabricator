<?php

final class UserContributionConduitAPIMethod extends UserConduitAPIMethod {

  public function getAPIMethodName() {
    return 'user.contribution';
  }

  public function getMethodDescription() {
    return pht('Get user contributions.');
  }

  protected function defineParamTypes() {
    return array(
      'userPHID' => 'optional phid',
    );
  }

  protected function defineReturnType() {
    return 'list<dict>';
  }

  protected function execute(ConduitAPIRequest $request) {
    $user_phid = $request->getUser()->getPHID();
    $other_user = $request->getValue('userPHID');

    if ($other_user) {
      $user_phid = $other_user;
    }

    $states = id(new LobbyStateQuery())
              ->setViewer(PhabricatorUser::getOmnipotentUser())
              ->withOwnerPHIDs(array($user_phid))
              ->needOwner(true)
              ->execute();
    $state = head($states);

    if (!$state) {
      return array(
        'error' => "State not found",
        'data' => array()
      );
    }
    $data = $this->buildGraph($state);
    return array(
      'error' => false,
      'data' => $data
    );
  }

  protected function buildGraph(LobbyState $state) {
    $table  = new LobbyStateTransaction();
    $conn_r = $table->establishConnection('r');

    // Aggregate hours work
    $logs = queryfx_all(
      $conn_r,
      'SELECT objectPHID, oldValue, newValue, dateCreated '.
      'FROM %T WHERE objectPHID=%s AND transactionType=%s'.
      'ORDER BY dateCreated ASC',
      $table->getTableName(),
      $state->getPHID(),
      LobbyStateStatusTransaction::TRANSACTIONTYPE);

    $graph = array();
    $lastEpoch = 0;
    foreach($logs as $log) {
      $epoch = (int) $log['dateCreated'];
      $lastEpoch = $epoch;

      $day_year = date('z', $epoch) + 1;
      $day_human = date('d M', $epoch);

      $o_v = (int) $log['oldValue'];
      $n_v = (int) $log['newValue'];

      if ($n_v == LobbyState::STATUS_IN_CHANNEL) {

        if (!isset($graph[$day_year])) {
          $graph[$day_year] = array(
            'title' => $day_human,
            'level' => 1,
            'epoch' => $epoch,
            'seconds' => 0,
          );
        } else {
          $graph[$day_year]['epoch'] = $epoch;
        }
      } else if($o_v == LobbyState::STATUS_IN_CHANNEL) {

        if (!isset($graph[$day_year])) {
          $graph[$day_year] = array(
            'title' => $day_human,
            'level' => 1,
            'epoch' => $lastEpoch,
            'seconds' => 0,
          );
        }

        $lastEpoch = $epoch;

        if (isset($graph[$day_year])) {
          $knownEpoch = $graph[$day_year]['epoch'];
          $diff = $lastEpoch - $knownEpoch;
          $graph[$day_year]['epoch'] = $epoch;
          $graph[$day_year]['seconds'] += $diff;
        }
      }

      $lastEpoch = $epoch;
    }

    // Aggregate tasks into adhoc + Maniphest
    $tasks = queryfx_all(
      $conn_r,
      'SELECT objectPHID, oldValue, newValue, dateCreated '.
      'FROM %T WHERE objectPHID=%s AND transactionType=%s'.
      'ORDER BY dateCreated ASC',
      $table->getTableName(),
      $state->getPHID(),
      LobbyStateCurrentTaskTransaction::TRANSACTIONTYPE);

    $elapsedTasks = array();
    foreach($tasks as $task) {
      $epoch = (int) $task['dateCreated'];
      $new_task = (string) str_replace('"', '',$task['newValue']);

      if ($new_task == "null") {
        $new_task = (string) str_replace('"', '',$task['oldValue']);
      } 

      $day_year = date('z', $epoch) + 1;
      if (!isset($elapsedTasks[$day_year])) {
        $elapsedTasks[$day_year] = array(
          'adhoc' => array(),
          'ticket' => array()
        );
      }

      $prefix = substr($new_task, 0, 1);
      $tail = substr($new_task, 1);

      $type = 'adhoc';
      if ($prefix == 'T' && is_numeric($tail)) {
        $type = 'ticket';
      }

      $elapsedTasks[$day_year][$type] = array_filter(array_unique(
        array_merge(
        $elapsedTasks[$day_year][$type],
        array($new_task)
      )));
    }

    $contrib = 'Just mingling';
    foreach($graph as $i => $data) {
      $hours = 1;
      $seconds = $graph[$i]['seconds'];
      if ($seconds > 0) {
        $hours = round($seconds/3600);
      }

      $graph[$i]['hours'] = $hours;

      $old_title = $graph[$i]['title'];

      // Finalize values
      $graph[$i]['level'] = $this->valueWorkHours($hours, $seconds);

      if (isset($elapsedTasks[$i])) {
        $distribution = array();
        foreach($elapsedTasks[$i] as $taskType => $items) {
          if (count($items) > 0) {
            // $distribution[] = pht('%s: %s', $taskType, implode(',', $items));
            $distribution[] = pht('%s', implode(',', $items));
          }
        }

        $contrib = implode(' / ', $distribution);
      }

      if ($hours > 0) {
        $graph[$i]['title'] = pht('%s (%s / %d hrs)',
          $old_title, $contrib, $hours);
      } else {
        $graph[$i]['title'] = pht('%s (%s / <1hr)',
          $old_title, $contrib);
      }
    }

    return $graph;
  }

  private function valueWorkHours($hours, $seconds) {
    if ($hours >= 8) {
      return 3;
    } else if ($hours < 8 && $hours > 4) {
      return 2;
    } else if ($hours > 1 || $seconds > 300) {
      return 1;
    }

    return 0;
  }

}
