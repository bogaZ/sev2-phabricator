<?php

final class SuiteNotificationsConduitAPIMethod
  extends SuiteConduitAPIMethod {

  public function getAPIMethodName() {
    return 'suite.notifications';
  }

  public function getMethodStatus() {
    return self::METHOD_STATUS_UNSTABLE;
  }

  public function getMethodDescription() {
    return pht('Get user notifications.');
  }

  private function getDefaultLimit() {
    return 100;
  }

  protected function defineParamTypes() {
    return array(
      'limit' => 'optional int (default '.$this->getDefaultLimit().')',
      'after' => 'optional int',
      'before' => 'optional int',
    );
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

    $query = id(new PhabricatorNotificationQuery())
      ->setViewer($viewer)
      ->withUserPHIDs(array($viewer->getPHID()));

    $limit = $request->getValue('limit');
    if (!$limit) {
      $limit = $this->getDefaultLimit();
    }

    $pager = id(new AphrontCursorPagerView())
      ->setPageSize($limit);

    $after = $request->getValue('after');
    if (strlen($after)) {
      $pager->setAfterID($after);
    }

    $before = $request->getValue('before');
    if (strlen($before)) {
      $pager->setBeforeID($before);
    }

    $stories = $query->executeWithCursorPager($pager);

    $builder = id(new SuiteNotificationBuilder($stories))
      ->setUser($viewer);

    $dict = $builder->buildDict();

    return array(
      'unread_count' => $viewer->getUnreadNotificationCount(),
      'stories' => $dict,
    );
  }
}
