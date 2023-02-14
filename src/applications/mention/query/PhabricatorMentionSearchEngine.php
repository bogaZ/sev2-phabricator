<?php

final class PhabricatorMentionSearchEngine
  extends PhabricatorApplicationSearchEngine {

  public function getResultTypeDescription() {
    return pht('Mentions');
  }

  public function getApplicationClassName() {
    return 'PhabricatorMentionApplication';
  }

  public function newQuery() {
    return new PhabricatorMentionQuery();
  }

  protected function buildQueryFromParameters(array $map) {
    $query = $this->newQuery();

    if ($map['mentionPHIDs']) {
      $query->withMentionPHIDs($map['mentionPHIDs']);
    }

    if ($map['startDate']) {
      $query->withStartDate($map['startDate']);
    }

    if ($map['endDate']) {
      $query->withEndDate($map['endDate']);
    }

    if ($map['callerPHID']) {
      $query->withCallerPHID($map['callerPHID']);
    }

    return $query;
  }

  protected function buildCustomSearchFields() {
    return array(
      id(new PhabricatorUsersSearchField())
        ->setKey('callerPHID')
        ->setAliases(array('user', 'users'))
        ->setLabel(pht('Users'))
        ->setDescription(
          pht('Search for users mention with specific user PHIDs.')),
      id(new PhabricatorUsersSearchField())
        ->setKey('mentionPHIDs')
        ->setAliases(array('mentionUser'))
        ->setLabel(pht('Mention User'))
        ->setDescription(
          pht('Search for users mention with specific user PHIDs.')),
      id(new PhabricatorSearchDateField())
        ->setLabel(pht('Mention After'))
        ->setKey('startDate')
        ->setDescription(
          pht('Find mention after a given time.')),
      id(new PhabricatorSearchDateField())
        ->setLabel(pht('Mention Before'))
        ->setKey('endDate')
        ->setDescription(
          pht('Find mention before a given time.')),
    );
  }

  protected function getURI($path) {
    return '/mention/'.$path;
  }

  protected function getBuiltinQueryNames() {
    return array(
      'mentioned' => pht('Mentioned To Me'),
      'authored' => pht('Created By Me'),
    );
  }

  public function buildSavedQueryFromBuiltin($query_key) {
    $query = $this->newSavedQuery();
    $query->setQueryKey($query_key);
    $viewer_phid = $this->requireViewer()->getPHID();

    switch ($query_key) {
      case 'mentioned':
        return $query->setParameter('mentionPHIDs', array($viewer_phid));
      case 'authored';
        return $query->setParameter('callerPHID', array($viewer_phid));
    }

    return parent::buildSavedQueryFromBuiltin($query_key);
  }

  protected function getRequiredHandlePHIDsForResultList(
    array $mention,
    PhabricatorSavedQuery $query) {
    return mpull($mention, 'getObjectPHID');
  }

  protected function renderResultList(
    array $mention,
    PhabricatorSavedQuery $query,
    array $handles) {

    assert_instances_of($mention, 'PhabricatorMention');

    $viewer = $this->requireViewer();

    $list = new PHUIObjectItemListView();
    foreach ($mention as $meant) {
      $user = id(new PhabricatorPeopleQuery())
        ->setViewer($viewer)
        ->withPHIDs(array($meant->getCallerPHID()))
        ->executeOne();

      $item = id(new PHUIObjectItemView())
        ->setUser($viewer)
        ->setObject($meant)
        ->setObjectName($meant->getMessage())
        ->addByline(
          pht(
            'Mentioned by %s',
            $user->getUserName()));

      $item->addAttribute(
        pht('Created on %s', phabricator_datetime(
          $meant->getDateCreated(), $viewer)));
      $item->addByline(
        pht(
          'Mentioned at %s',
          $handles[$meant->getObjectPHID()]->renderLink()));
      $list->addItem($item);
    }

    $result = new PhabricatorApplicationSearchResultView();
    $result->setObjectList($list);
    $result->setNoDataString(pht('No user mention found.'));

    return $result;
  }
}
