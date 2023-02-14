<?php

final class SuiteNotificationsMarkAllReadConduitAPIMethod
  extends SuiteConduitAPIMethod {

  public function getAPIMethodName() {
    return 'suite.notifications.mark_all_read';
  }

  public function getMethodStatus() {
    return self::METHOD_STATUS_UNSTABLE;
  }

  public function getMethodDescription() {
    return pht('Mark all user notifications as read.');
  }

  protected function defineParamTypes() {
    return array();
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $this->getViewer();
    $user = $request->getUser();

    if (!$user) {
      throw new ConduitException('ERR_USER_NOT_FOUND');
    }

    $unread_count = $viewer->getUnreadNotificationCount();

    // Get latest chrono key
    $query = id(new PhabricatorNotificationQuery())
      ->setViewer($viewer)
      ->withUserPHIDs(array($viewer->getPHID()))
      ->setLimit(1);

    $stories = $query->execute();
    if (count($stories) == 0) {
      return array('cleared_count' => 0);
    }

    $chronokeys = array_filter(mpull($stories, 'getChronologicalKey'));

    if (count($chronokeys) == 0) {
      return array('cleared_count' => 0);
    }

    $chrono_key = head($chronokeys);

    $table = new PhabricatorFeedStoryNotification();

    queryfx(
      $table->establishConnection('w'),
      'UPDATE %T SET hasViewed = 1 '.
      'WHERE userPHID = %s AND hasViewed = 0 and chronologicalKey <= %s',
      $table->getTableName(),
      $viewer->getPHID(),
      $chrono_key);

    PhabricatorUserCache::clearCache(
      PhabricatorUserNotificationCountCacheType::KEY_COUNT,
      $viewer->getPHID());

    return array(
      'cleared_count' => $unread_count,
    );
  }
}
