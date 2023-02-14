<?php

final class DiffusionRepositoryBuildInfoFileSearchEngine
  extends PhabricatorApplicationSearchEngine {

  public function getResultTypeDescription() {
    return pht('Build Info Files');
  }

  public function getApplicationClassName() {
    return 'PhabricatorDiffusionApplication';
  }

  public function canUseInPanelContext() {
    return false;
  }

  public function newQuery() {
    $controller = $this->getController();
    $request = $controller->getRequest();

    $build_id = $request->getURIData('build_id');
    $build = id(new PhabricatorRepositoryBuildInfoQuery())
      ->setViewer($request->getUser())
      ->withIDs(array($build_id))
      ->executeOne();

    $query = new PhabricatorRepositoryBuildInfoFileQuery();
    $query->withBuildPHIDs(array($build->getPHID()));
    return $query;
  }

  protected function buildCustomSearchFields() {
    return array(
      id(new PhabricatorUsersSearchField())
        ->setKey('authorPHIDs')
        ->setAliases(array('author', 'authors'))
        ->setLabel(pht('Authors')),
      id(new PhabricatorSearchThreeStateField())
        ->setKey('explicit')
        ->setLabel(pht('Upload Source'))
        ->setOptions(
          pht('(Show All)'),
          pht('Show Only Manually Uploaded Files'),
          pht('Hide Manually Uploaded Files')),
      id(new PhabricatorSearchDateField())
        ->setKey('createdStart')
        ->setLabel(pht('Created After')),
      id(new PhabricatorSearchDateField())
        ->setKey('createdEnd')
        ->setLabel(pht('Created Before')),
      id(new PhabricatorSearchTextField())
        ->setLabel(pht('Name Contains'))
        ->setKey('name')
        ->setDescription(pht('Search for files by name substring.')),
    );
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

    return $query;
  }

  protected function getURI($path) {
    return $path;
  }

  protected function getBuiltinQueryNames() {
    $names = array();

    $names += array(
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
      case 'authored':
        $author_phid = array($this->requireViewer()->getPHID());
        return $query
          ->setParameter('authorPHIDs', $author_phid)
          ->setParameter('explicit', true);
    }

    return parent::buildSavedQueryFromBuiltin($query_key);
  }

  protected function getRequiredHandlePHIDsForResultList(
    array $files,
    PhabricatorSavedQuery $query) {
    return mpull($files, 'getPHID');
  }

  protected function renderResultList(
    array $files,
    PhabricatorSavedQuery $query,
    array $handles) {

    assert_instances_of($files, 'PhabricatorRepositoryBuildInfoFile');

    $request = $this->getRequest();
    if ($request) {
      $highlighted_ids = $request->getStrList('h');
    } else {
      $highlighted_ids = array();
    }

    $viewer = $this->requireViewer();

    $highlighted_ids = array_fill_keys($highlighted_ids, true);

    $list_view = id(new PHUIObjectItemListView())
      ->setUser($viewer);

    $file_phids = mpull($files, 'getFilePHID');

    $files = id(new PhabricatorFileQuery())
      ->setViewer($viewer)
      ->withPHIDs($file_phids)
      ->execute();

    foreach ($files as $file) {
      $id = $file->getID();
      $phid = $file->getPHID();
      $name = $file->getName();
      $file_uri = "/file/info/{$phid}/";

      $date_created = phabricator_date($file->getDateCreated(), $viewer);
      $uploaded = pht('Uploaded on %s', $date_created);

      $item = id(new PHUIObjectItemView())
        ->setObject($file)
        ->setObjectName("F{$id}")
        ->setHeader($name)
        ->setHref($file_uri)
        ->addAttribute($uploaded)
        ->addIcon('none', phutil_format_bytes($file->getByteSize()));

      $ttl = $file->getTTL();
      if ($ttl !== null) {
        $item->addIcon('blame', pht('Temporary'));
      }

      if ($file->getIsPartial()) {
        $item->addIcon('fa-exclamation-triangle orange', pht('Partial'));
      }

      if (isset($highlighted_ids[$id])) {
        $item->setEffect('highlighted');
      }

      $remove_uri = "delete/?phid=$phid";
      $item->addAction(
        id(new PHUIListItemView())
          ->setIcon('fa-times')
          ->setName(pht('Remove'))
          ->setHref($remove_uri)
          ->setWorkflow(true));

      $list_view->addItem($item);
    }

    $list_view->appendChild(id(new PhabricatorGlobalUploadTargetView())
      ->setUser($viewer));


    $result = new PhabricatorApplicationSearchResultView();
    $result->setContent($list_view);

    return $result;
  }

  protected function getNewUserBody() {
    $create_button = id(new PHUIButtonView())
      ->setTag('a')
      ->setText(pht('Upload a File'))
      ->setHref('upload/')
      ->setColor(PHUIButtonView::GREEN);

    $icon = $this->getApplication()->getIcon();
    $app_name = 'Build Info File Management';
    $view = id(new PHUIBigInfoView())
      ->setIcon($icon)
      ->setTitle(pht('Welcome to %s', $app_name))
      ->setDescription(
        pht('Just a place for files.'))
      ->addAction($create_button);

      return $view;
  }

}
