<?php

final class LobbyFileResultListView extends AphrontView {

  private $baseUri;
  private $noDataString;
  private $files;
  private $user;

  public function setBaseUri($base_uri) {
    $this->baseUri = $base_uri;
    return $this;
  }

  public function setUser(PhabricatorUser $user) {
    $this->user = $user;
    return $this;
  }

  protected function getUser() {
    return $this->user;
  }

  public function setFiles(array $files) {
    $this->files = $files;
    return $this;
  }

  public function getFiles() {
    return $this->files;
  }

  public function render() {
    $files = $this->getFiles();
    assert_instances_of($files, 'PhabricatorFile');

    $handles = id(new PhabricatorHandleQuery())
          ->setViewer(PhabricatorUser::getOmnipotentUser())
          ->withPHIDs(mpull($files,'getAuthorPHID'))
          ->execute();
    $handles = mpull($handles, null, 'getPHID');

    $highlighted_ids = array();
    $viewer = $this->getUser();

    $highlighted_ids = array_fill_keys($highlighted_ids, true);

    $list_view = id(new PHUIObjectItemListView())
      ->setUser($viewer);

    foreach ($files as $file) {
      $id = $file->getID();
      $phid = $file->getPHID();
      $name = $file->getName();
      $file_uri = "/file/info/{$phid}/";

      $date_created = phabricator_date($file->getDateCreated(), $viewer);
      $author_phid = $file->getAuthorPHID();
      if ($author_phid) {
        $author_link = $handles[$author_phid]->renderLink();
        $uploaded = pht('Uploaded by %s on %s', $author_link, $date_created);
      } else {
        $uploaded = pht('Uploaded on %s', $date_created);
      }

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

      $list_view->addItem($item);
    }

    return $list_view;
  }

}
