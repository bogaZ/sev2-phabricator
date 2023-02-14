<?php

final class LobbyAphlict {

  /**
   * Broadcast join channel event
   * @param string $thread_phid
   * @param string $user_phid
   * @param string $user_data
   */
  public static function broadcastJoiningChannel($thread_phid,
    $user_phid, $user_data) {

      if (PhabricatorNotificationClient::isEnabled()) {
        // Notify subscribers
        PhabricatorNotificationClient::tryToPostMessage(array(
          'subscribers' => [$thread_phid],
          'threadPHID' => $thread_phid,
          'type' => Lobby::CHANNEL_STATE,
          'state' => Lobby::CHANNEL_STATE_JOINING,
          'target' => $user_phid,
          'target_data' => $user_data
        ));
      }
  }


  /**
   * Broadcast leave channel event
   * @param string $thread_phid
   * @param string $user_phid
   * @param string $user_data
   */
  public static function broadcastLeavingChannel($thread_phid,
    $user_phid, $user_data) {

      if (PhabricatorNotificationClient::isEnabled()) {
        // Notify subscribers
        PhabricatorNotificationClient::tryToPostMessage(array(
          'subscribers' => [$thread_phid],
          'threadPHID' => $thread_phid,
          'type' => Lobby::CHANNEL_STATE,
          'state' => Lobby::CHANNEL_STATE_LEAVING,
          'target' => $user_phid,
          'target_data' => $user_data
        ));
      }
  }

  /**
   * Broadcast lobbyst
   */
  public static function broadcastLobby()
  {
    if (PhabricatorNotificationClient::isEnabled()) {
      PhabricatorNotificationClient::tryToPostMessage(array(
          'subscribers' => [Lobby::TOPIC],
          'type' => 'lobby',
        ));
    }
  }
}
