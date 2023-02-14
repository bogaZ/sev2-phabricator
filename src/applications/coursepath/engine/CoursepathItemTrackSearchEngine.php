<?php

final class CoursepathItemTrackSearchEngine
  extends PhabricatorApplicationSearchEngine {

  public function getResultTypeDescription() {
    return pht('Teachable Courses');
  }

  public function getApplicationClassName() {
    return 'PhabricatorCoursepathApplication';
  }

  public function newQuery() {
    $request = $this->getController()->getRequest();
    $item_id = $request->getURIData('id');

    $item = id(new CoursepathItem())
      ->loadOneWhere('id = %s', $item_id);

    return id(new CoursepathItemTrackQuery())
        ->withItemPHIDs(array($item->getPHID()));
  }

  protected function buildCustomSearchFields() {
    return array(
      id(new PhabricatorSearchTextField())
        ->setLabel(pht('Course Name'))
        ->setKey('name')
        ->setDescription(
          pht('Search for teachable course by name.')),
    );
  }

  protected function buildQueryFromParameters(array $map) {
    $query = $this->newQuery();

    if ($map['name']) {
      $query->withName($map['name']);
    }

    return $query;
  }

  protected function getURI($path) {
    $request = $this->getController()->getRequest();
    $item_id = $request->getURIData('id');
    return "/coursepath/item/view/$item_id/tracks/$path";
  }

  protected function getBuiltinQueryNames() {
    $names = array();

    $names['all'] = pht('All Courses');

    return $names;
  }

  public function buildSavedQueryFromBuiltin($query_key) {
    $query = $this->newSavedQuery();
    $query->setQueryKey($query_key);

    switch ($query_key) {
      case 'all':
        return $query;
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

  protected function renderResultList(
    array $tracks,
    PhabricatorSavedQuery $query,
    array $handles) {
    assert_instances_of($tracks, 'CoursepathItemTrack');

    $request = $this->getController()->getRequest();
    $item_id = $request->getURIData('id');

    $viewer = $this->requireViewer();

    $list = id(new PHUIObjectItemListView());
    foreach ($tracks as $track) {
      $creator_handle = $viewer->renderHandle($track->getCreatorPHID());
      $create_date = phabricator_date($track->getDateCreated(), $viewer);

      $creator_info = pht(
        'Added by %s on %s',
        $creator_handle->render(),
        $create_date);

      $remove_uri = 'view/'.$track->getID().'/remove/';
      $item = id(new PHUIObjectItemView())
      ->setHeader($track->getName())
      ->setIcon('fa fa-book')
      ->setSubHead($creator_info)
      ->addAction(
        id(new PHUIListItemView())
          ->setIcon('fa-times')
          ->setName(pht('Remove'))
          ->setHref($remove_uri)
          ->setWorkflow(true));

      $list->addItem($item);
    }

    $result = new PhabricatorApplicationSearchResultView();
    $result->setObjectList($list);
    $result->setNoDataString(pht('No Teachable Course data found.'));

    return $result;

  }

}
