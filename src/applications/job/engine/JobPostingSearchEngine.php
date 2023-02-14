<?php

final class JobPostingSearchEngine
  extends PhabricatorApplicationSearchEngine {

  public function getResultTypeDescription() {
    return pht('Job Posting');
  }

  public function getApplicationClassName() {
    return 'PhabricatorJobApplication';
  }

  public function newQuery() {
    return id(new JobPostingQuery())
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->needActor($this->requireViewer());
  }

  protected function buildCustomSearchFields() {
    return array(
      id(new PhabricatorSearchTextField())
        ->setLabel('Name')
        ->setKey('name'),
      id(new PhabricatorSearchTextField())
        ->setLabel('Job Location')
        ->setKey('location'),
      id(new PhabricatorSearchDatasourceField())
        ->setLabel(pht('Invited'))
        ->setKey('invitedPHIDs')
        ->setDatasource(new PhabricatorCalendarInviteeDatasource()),
      id(new PhabricatorSearchDateControlField())
        ->setLabel(pht('Occurs After'))
        ->setKey('rangeStart'),
      id(new PhabricatorSearchDateControlField())
        ->setLabel(pht('Occurs Before'))
        ->setKey('rangeEnd')
        ->setAliases(array('rangeEnd')),
      id(new PhabricatorSearchSelectField())
        ->setLabel('Salary Currency')
        ->setKey('salaryCurrency')
        ->setDescription(pht('Supported values [%s, %s]', 'idr', 'dollar'))
        ->setOptions(array(
          'idr' => pht('IDR'),
          'dollar' => pht('Dollar'),
        )),
      id(new PhabricatorSearchTextField())
        ->setLabel('Salary Start From')
        ->setKey('salaryFrom'),
      id(new PhabricatorSearchTextField())
        ->setLabel('Salary End To')
        ->setKey('salaryTo'),
      id(new PhabricatorSearchCheckboxesField())
        ->setKey('isLead')
        ->setOptions(array(
          'is_lead' => pht('Show only leads job posting.'),
          )),
      id(new PhabricatorSearchCheckboxesField())
        ->setKey('upcoming')
        ->setOptions(array(
          'upcoming' => pht('Show only upcoming job posting.'),
          )),
      id(new PhabricatorSearchSelectField())
        ->setLabel(pht('Cancelled Job'))
        ->setKey('isCancelled')
        ->setOptions($this->getCancelledOptions())
        ->setDefault('active'),
    );
  }

  private function getCancelledOptions() {
    return array(
      'active' => pht('Active Events Only'),
      'cancelled' => pht('Cancelled Events Only'),
      'both' => pht('Both Cancelled and Active Events'),
    );
  }

  protected function buildQueryFromParameters(array $map) {
    $query = $this->newQuery();

    if ($map['invitedPHIDs']) {
      $query->withInvitedPHIDs($map['invitedPHIDs']);
    }

    $range_start = $map['rangeStart'];
    $range_end = $map['rangeEnd'];

    if ($map['upcoming'] && $map['upcoming'][0] == 'upcoming') {
      $upcoming = true;
    } else {
      $upcoming = false;
    }

    if ($map['isLead'] && $map['isLead'][0] == 'is_lead') {
      $query->withIsLead(true);
    } else {
      $query->withIsLead(false);
    }

    if ($map['name']) {
      $query->withName($map['name']);
    }

    if ($map['salaryFrom'] && $map['salaryTo']) {
      $query->withSalaryFrom($map['salaryFrom']);
      $query->withSalaryEnd($map['salaryTo']);
    }

    if ($map['location']) {
      $query->withLocation($map['location']);
    }

    list($range_start, $range_end) = $this->getQueryDateRange(
      $range_start,
      $range_end,
      $upcoming);

    $query->withDateRange($range_start, $range_end);

    switch ($map['isCancelled']) {
      case 'active':
        $query->withIsCancelled(false);
        break;
      case 'cancelled':
        $query->withIsCancelled(true);
        break;
    }

    return $query;
  }

  private function getQueryDateRange(
    $start_date_wild,
    $end_date_wild,
    $upcoming) {

    $start_date_value = $this->getSafeDate($start_date_wild);
    $end_date_value = $this->getSafeDate($end_date_wild);

    $viewer = $this->requireViewer();
    $timezone = new DateTimeZone($viewer->getTimezoneIdentifier());
    $min_range = null;
    $max_range = null;

    $min_range = $start_date_value->getEpoch();
    $max_range = $end_date_value->getEpoch();

    if ($upcoming) {
      $now = PhabricatorTime::getNow();
      if ($min_range) {
        $min_range = max($now, $min_range);
      } else {
        $min_range = $now;
      }
    }

    return array($min_range, $max_range);
  }

  protected function getURI($path) {
    return '/job/'.$path;
  }

  protected function getBuiltinQueryNames() {
    $names = array(
      'upcoming' => pht('Upcoming Jobs'),
      'leads' => pht('All Lead Jobs'),
      'all' => pht('All Jobs'),
    );

    return $names;
  }

  public function buildSavedQueryFromBuiltin($query_key) {
    $query = $this->newSavedQuery();
    $query->setQueryKey($query_key);

    switch ($query_key) {
      case 'open':
        // @TODO : add active filter
        return $query;
      case 'leads':
        return $query->setParameter('isLead', array('is_lead'));
      case 'all':
      default:
        return $query;
    }

    return parent::buildSavedQueryFromBuiltin($query_key);
  }

  protected function getRequiredHandlePHIDsForResultList(
    array $items,
    PhabricatorSavedQuery $query) {

    $phids = array();

    return $phids;
  }

  private function getSafeDate($value) {
    $viewer = $this->requireViewer();
    if ($value) {
      // ideally this would be consistent and always pass in the same type
      if ($value instanceof AphrontFormDateControlValue) {
        return $value;
      } else {
        $value = AphrontFormDateControlValue::newFromWild($viewer, $value);
      }
    } else {
      $value = AphrontFormDateControlValue::newFromEpoch(
        $viewer,
        PhabricatorTime::getTodayMidnightDateTime($viewer)->format('U'));
      $value->setEnabled(false);
    }

    $value->setOptional(true);

    return $value;
  }

  protected function renderResultList(
    array $items,
    PhabricatorSavedQuery $query,
    array $handles) {
    assert_instances_of($items, 'JobPosting');

    $viewer = $this->requireViewer();

    $list = id(new PHUIObjectItemListView());
    foreach ($items as $posting) {
      $remove_uri = 'remove/'.$posting->getID().'/';
      $item = id(new PHUIObjectItemView())
        ->setHeader($posting->getName())
        ->setIcon('fa fa-thumb-tack')
        ->setHref('/job/view/'.$posting->getID().'/')
        ->setSubHead($posting->getDescription());

      if ($posting->getIsCancelled()) {
        $item->setDisabled(true);
        $item->addIcon('fa-ban', pht('Cancelled'));
      }

      $list->addItem($item);
    }

    $result = new PhabricatorApplicationSearchResultView();
    $result->setObjectList($list);
    $result->setNoDataString(pht('No job posting found.'));

    return $result;

  }

  protected function getNewUserBody() {
    $create_button = id(new PHUIButtonView())
      ->setTag('a')
      ->setText(pht('Create a Job Posting'))
      ->setHref('/job/edit/form/default')
      ->setColor(PHUIButtonView::GREEN);

    $icon = $this->getApplication()->getIcon();
    $app_name =  $this->getApplication()->getName();
    $view = id(new PHUIBigInfoView())
      ->setIcon($icon)
      ->setTitle(pht('Welcome to %s', $app_name))
      ->setDescription(
        pht('Job let you posting a job and manage applicants'.
          ' throughout Suite.'))
      ->addAction($create_button);

      return $view;
  }

}
