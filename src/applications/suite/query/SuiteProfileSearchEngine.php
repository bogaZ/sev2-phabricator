<?php

final class SuiteProfileSearchEngine
  extends PhabricatorApplicationSearchEngine {

  public function getResultTypeDescription() {
    return pht('Users');
  }

  public function getApplicationClassName() {
    return 'PhabricatorSuiteApplication';
  }

  public function newQuery() {
    return id(new PhabricatorPeopleQuery())
      ->needPrimaryEmail(true)
      ->needProfileImage(true);
  }

  protected function buildCustomSearchFields() {
    $fields = array(
      id(new PhabricatorSearchStringListField())
        ->setLabel(pht('Usernames'))
        ->setKey('usernames')
        ->setAliases(array('username'))
        ->setDescription(pht('Find users by exact username.')),
      id(new PhabricatorSearchTextField())
        ->setLabel(pht('Name Contains'))
        ->setKey('nameLike')
        ->setDescription(
          pht('Find users whose usernames contain a substring.')),
      id(new PhabricatorSearchThreeStateField())
        ->setLabel(pht('Active Subscribers'))
        ->setKey('isSuiteSubscribed')
        ->setOptions(
          pht('(Show All)'),
          pht('Show Only Active subscribers'),
          pht('Hide Active subscribers'))
        ->setDescription(
          pht(
            'Pass true to find only active subscribers, or false to omit '.
            'active subsribers.')),
      id(new PhabricatorSearchThreeStateField())
        ->setLabel(pht('Online'))
        ->setKey('isSuiteOnline')
        ->setOptions(
          pht('(Show All)'),
          pht('Show Only Online Users'),
          pht('Hide Online Users'))
        ->setDescription(
          pht(
            'Pass true to find only online users, or false to omit '.
            'online users.')),
      id(new PhabricatorSearchThreeStateField())
        ->setLabel(pht('Disabled'))
        ->setKey('isSuiteDisabled')
        ->setOptions(
          pht('(Show All)'),
          pht('Show Only Disabled Users'),
          pht('Hide Disabled Users'))
        ->setDescription(
          pht(
            'Pass true to find only disabled users, or false to omit '.
            'disabled users.')),
      id(new PhabricatorSearchThreeStateField())
        ->setLabel(pht('Development'))
        ->setKey('isForDev')
        ->setOptions(
          pht('(Show All)'),
          pht('Show Only For Development'),
          pht('Hide Only Development'))
        ->setDescription(
          pht(
            'Pass true to find users only for '.
            'Development.')),
    );

    $fields[] = id(new PhabricatorSearchDateField())
      ->setKey('createdStart')
      ->setLabel(pht('Joined After'))
      ->setDescription(
        pht('Find user accounts created after a given time.'));

    $fields[] = id(new PhabricatorSearchDateField())
      ->setKey('createdEnd')
      ->setLabel(pht('Joined Before'))
      ->setDescription(
        pht('Find user accounts created before a given time.'));

    return $fields;
  }

  protected function getDefaultFieldOrder() {
    return array(
      '...',
      'createdStart',
      'createdEnd',
    );
  }

  protected function buildQueryFromParameters(array $map) {
    $query = $this->newQuery();

    $viewer = $this->requireViewer();

    // If the viewer can't browse the user directory, restrict the query to
    // just the user's own profile. This is a little bit silly, but serves to
    // restrict users from creating a dashboard panel which essentially just
    // contains a user directory anyway.
    $can_browse = PhabricatorPolicyFilter::hasCapability(
      $viewer,
      $this->getApplication(),
      PhabricatorSuiteCapabilityManageUser::CAPABILITY);
    if (!$can_browse) {
      $query->withPHIDs(array($viewer->getPHID()));
    }

    if ($map['usernames']) {
      $query->withUsernames($map['usernames']);
    }

    if ($map['nameLike']) {
      $query->withNameLike($map['nameLike']);
    }

    if ($map['isForDev'] !== null) {
      $query->withIsForDev($map['isForDev']);
    }

    if ($map['isSuiteSubscribed'] !== null) {
      $query->withIsSuiteSubscribed($map['isSuiteSubscribed']);
    }

    if ($map['isSuiteDisabled'] !== null) {
      $query->withIsDisabled($map['isSuiteDisabled']);
      $query->withIsSuiteDisabled($map['isSuiteDisabled']);
    }

    if ($map['isSuiteOnline'] !== null) {
      $query->withIsSuiteOnline($map['isSuiteOnline']);
    }

    if ($map['createdStart']) {
      $query->withDateCreatedAfter($map['createdStart']);
    }

    if ($map['createdEnd']) {
      $query->withDateCreatedBefore($map['createdEnd']);
    }

    $query->withIsSuite(true);

    return $query;
  }

  protected function getURI($path) {
    return '/suite/users/'.$path;
  }

  protected function getBuiltinQueryNames() {
    $names = array(
      'active' => pht('Active'),
      'all' => pht('All'),
    );

    return $names;
  }

  public function buildSavedQueryFromBuiltin($query_key) {
    $query = $this->newSavedQuery();
    $query->setQueryKey($query_key);

    switch ($query_key) {
      case 'all':
        return $query;
      case 'active':
        return $query
          ->setParameter('isSuiteDisabled', false);
    }

    return parent::buildSavedQueryFromBuiltin($query_key);
  }

  protected function renderResultList(
    array $users,
    PhabricatorSavedQuery $query,
    array $handles) {

    assert_instances_of($users, 'PhabricatorUser');

    $request = $this->getRequest();
    $viewer = $this->requireViewer();

    $list = new PHUIObjectItemListView();

    $is_approval = ($query->getQueryKey() == 'approval');

    foreach ($users as $user) {
      $primary_email = $user->loadPrimaryEmail();
      if ($primary_email && $primary_email->getIsVerified()) {
        $email = pht('Verified');
      } else {
        $email = pht('Unverified');
      }

      $item = new PHUIObjectItemView();
      $item->setHeader($user->getFullName())
        ->setHref('/suite/users/view/'.$user->getID().'/')
        ->addAttribute(phabricator_datetime($user->getDateCreated(), $viewer))
        ->addAttribute($email)
        ->setImageURI($user->getProfileImageURI());

      if ($is_approval && $primary_email) {
        $item->addAttribute($primary_email->getAddress());
      }

      if ($user->getIsDisabled() || $user->getIsSuiteDisabled()) {
        $item->addIcon('fa-ban', pht('Disabled'));
        $item->setDisabled(true);
      }

      if ($user->getIsForDev()) {
        $item->addIcon('fa-exclamation', pht('Dev'));
      }

      if (!$is_approval) {
        if (!$user->getIsApproved()) {
          $item->addIcon('fa-clock-o', pht('Needs Approval'));
        }
      }

      if ($user->getIsAdmin()) {
        $item->addIcon('fa-star', pht('Admin'));
      }

      if ($user->getIsConnect()) {
        $item->addIcon('fa-id-card-o', pht('Connect'));
      }

      if ($user->getIsSystemAgent()) {
        $item->addIcon('fa-desktop', pht('Bot'));
      }

      if ($viewer->getIsAdmin()) {
        if ($user->getIsEnrolledInMultiFactor()) {
          $item->addIcon('fa-lock', pht('Has MFA'));
        }
      }

      if ($viewer->getIsAdmin()) {
        $user_id = $user->getID();
        $item->addAction(
          id(new PHUIListItemView())
            ->setIcon($user->getIsSuiteDisabled()
                      ? 'fa-check'
                      : 'fa-ban')
            ->setName($user->getIsSuiteDisabled()
                      ? pht('Enable')
                      : pht('Disable'))
            ->setWorkflow(true)
            ->setHref($this->getApplicationURI('users/disable/'.$user_id.'/')));
        $item->addAction(
          id(new PHUIListItemView())
            ->setIcon($user->getIsForDev()
                      ? 'fa-flag-o'
                      : 'fa-flag')
            ->setName($user->getIsForDev()
                      ? pht('Mark as non-dev')
                      : pht('Mark as dev'))
            ->setWorkflow(true)
            ->setHref($this->getApplicationURI('users/dev/'.$user_id.'/')));
      }

      $list->addItem($item);
    }

    $result = new PhabricatorApplicationSearchResultView();
    $result->setObjectList($list);
    $result->setNoDataString(pht('No accounts found.'));

    return $result;
  }

  protected function newExportFields() {
    return array(
      id(new PhabricatorStringExportField())
        ->setKey('username')
        ->setLabel(pht('Username')),
      id(new PhabricatorStringExportField())
        ->setKey('realName')
        ->setLabel(pht('Real Name')),
    );
  }

  protected function newExportData(array $users) {
    $viewer = $this->requireViewer();

    $export = array();
    foreach ($users as $user) {
      $export[] = array(
        'username' => $user->getUsername(),
        'realName' => $user->getRealName(),
      );
    }

    return $export;
  }

}
