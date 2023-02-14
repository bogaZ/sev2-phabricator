<?php

final class CoursepathItemTestSubmissionSearchEngine
  extends PhabricatorApplicationSearchEngine {

  public function getResultTypeDescription() {
    return pht('Test Submissions');
  }

  public function getApplicationClassName() {
    return 'PhabricatorCoursepathApplication';
  }

  public function newQuery() {
    $request = $this->getController()->getRequest();
    $item_id = $request->getURIData('id');

    $item = id(new CoursepathItem())
      ->loadOneWhere('id = %s', $item_id);

    return id(new CoursepathItemTestQuery())
        ->needSubmissions(true)
        ->withViewer($this->requireViewer())
        ->withItemPHIDs(array($item->getPHID()));
  }

  protected function buildCustomSearchFields() {
    return array(
      id(new PhabricatorSearchTextField())
        ->setLabel(pht('Test Name'))
        ->setKey('title')
        ->setDescription(pht('Search for submission name by test name.')),
      id(new PhabricatorSearchCheckboxesField())
        ->setKey('types')
        ->setLabel(pht('Types'))
        ->setOptions(
          id(new CoursepathItemTest())
            ->getTypeMap()),
      id(new PhabricatorSearchCheckboxesField())
        ->setKey('severities')
        ->setLabel(pht('Severities'))
        ->setOptions(
          id(new CoursepathItemTest())
            ->getSeverityMap()),
      id(new PhabricatorSearchCheckboxesField())
        ->setKey('isNotAutomaticallyGraded')
        ->setLabel(pht('Is WPM'))
        ->setOptions(
          id(array(
            1 => 'isNotAutomaticallyGraded',
          ))),
      id(new PhabricatorSearchDateControlField())
        ->setLabel(pht('Date Start'))
        ->setKey('rangeStart'),
      id(new PhabricatorSearchDateControlField())
        ->setLabel(pht('Date End'))
        ->setKey('rangeEnd'),
      id(new PhabricatorUsersSearchField())
        ->setLabel(pht('Submitter'))
        ->setKey('creatorPHIDs')
        ->setAliases(array('submitter', 'submitters'))
        ->setDescription(
          pht('Search based by submitter.')),
    );
  }

  protected function buildQueryFromParameters(array $map) {
    $query = $this->newQuery();

    if ($map['types']) {
      $query->withTypes($map['types']);
    }

    if ($map['title']) {
      $query->withTitles($map['title']);
    }

    if ($map['severities']) {
      $query->withSeverities($map['severities']);
    }

    if ($map['isNotAutomaticallyGraded']) {
      $query->withAutoGrade($map['isNotAutomaticallyGraded']);
    }

    if ($map['creatorPHIDs']) {
      $query->withSubmitterPHIDs($map['creatorPHIDs']);
    }

    if ($map['rangeStart']) {
      $query->withStartDate($map['rangeStart']->getEpoch());
    }

    if ($map['rangeEnd']) {
      $query->withEndDate($map['rangeEnd']->getEpoch());
    }

    return $query;
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

  protected function getURI($path) {
    $request = $this->getController()->getRequest();
    $item_id = $request->getURIData('id');
    return "/coursepath/item/view/$item_id/submissions/$path";
  }

  protected function getBuiltinQueryNames() {
    $names = array();

    $names['daily'] = pht('Daily Submissions');
    $names['quiz'] = pht('Quiz Submissions');
    $names['exercise'] = pht('Exercise Submissions');

    return $names;
  }

  public function buildSavedQueryFromBuiltin($query_key) {
    $query = $this->newSavedQuery();
    $query->setQueryKey($query_key);

    switch ($query_key) {
      case 'daily':
        return $query->setParameter(
          'types',
          array(
            CoursepathItemTest::TYPE_DAILY,
          ));
      case 'quiz':
        return $query->setParameter(
          'types',
          array(
            CoursepathItemTest::TYPE_QUIZ,
          ));
      case 'exercise':
        return $query->setParameter(
          'types',
          array(
            CoursepathItemTest::TYPE_EXERCISE,
          ));
    }

    return parent::buildSavedQueryFromBuiltin($query_key);
  }

  protected function getRequiredHandlePHIDsForResultList(
    array $items,
    PhabricatorSavedQuery $query) {

    $phids = array();

    return $phids;
  }

  protected function renderResultList(
    array $tests,
    PhabricatorSavedQuery $query,
    array $handles) {
    assert_instances_of($tests, 'CoursepathItemTest');

    $request = $this->getController()->getRequest();
    $item_id = $request->getURIData('id');

    $viewer = $this->requireViewer();

    $list = id(new PHUIObjectItemListView());
    foreach ($tests as $test) {
      foreach ($test->getSubmissions() as $submission) {
        $creator_handle = $viewer->renderHandle($submission->getCreatorPHID());
        $create_date = phabricator_date($submission->getDateCreated(), $viewer);

        $submitter_info = pht(
          'Submitted by %s on %s',
          $creator_handle->render(),
          $create_date);

        if ($test->getStack()) {
          $object = pht('[%s][%s][%s]',
            ucwords($test->getType()),
            $test->getStack(),
            ucwords($test->getSeverity()));
        } else {
          $object = pht('[%s][%s]',
            ucwords($test->getType()),
            ucwords($test->getSeverity()));
        }

        $score = phutil_tag('strong', array(), $submission->getScore());
        $uri = '/coursepath/item/view/'.$item_id.'/';
        $item = id(new PHUIObjectItemView())
        ->setHeader(strtoupper($test->getTitle()))
        ->setIcon('fa fa-pencil')
        ->setHref($uri)
        ->setObjectName($object)
        ->setSubHead($submitter_info)
        ->addAttribute(pht('Score : %s', $score))
        ->addAction(
          id(new PHUIListItemView())
            ->setIcon('fa-plus')
            ->setName(pht('Add / Update Score'))
            ->setHref($uri.'submissions/view/'.$submission->getID().'/score')
            ->setWorkflow(true));

        $list->addItem($item);
      }
    }

    $result = new PhabricatorApplicationSearchResultView();
    $result->setObjectList($list);
    $result->setNoDataString(pht('No Submissions data found.'));

    return $result;

  }

}
