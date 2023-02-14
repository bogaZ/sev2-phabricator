<?php

final class SuiteProjectListView extends AphrontView {

  private $baseUri;
  private $projects;
  private $showMember;
  private $showWatching;
  private $noDataString;

  public function setBaseUri($base_uri) {
    $this->baseUri = $base_uri;
    return $this;
  }

  public function setProjects(array $projects) {
    $this->projects = $projects;
    return $this;
  }

  public function getProjects() {
    return $this->projects;
  }

  public function setShowWatching($watching) {
    $this->showWatching = $watching;
    return $this;
  }

  public function setShowMember($member) {
    $this->showMember = $member;
    return $this;
  }

  public function setNoDataString($text) {
    $this->noDataString = $text;
    return $this;
  }

  public function renderList() {
    $viewer = $this->getUser();
    $viewer_phid = $viewer->getPHID();
    $projects = $this->getProjects();

    $handles = $viewer->loadHandles(mpull($projects, 'getPHID'));

    $no_data = pht('No projects found.');
    if ($this->noDataString) {
      $no_data = $this->noDataString;
    }

    $list = id(new PHUIObjectItemListView())
      ->setUser($viewer)
      ->setBig(true)
      ->setNoDataString($no_data);

    foreach ($projects as $key => $project) {
      $id = $project->getID();



      $item = id(new PHUIObjectItemView())
        ->setObject($project)
        ->setHeader($project->getName())
        ->setHref("/project/view/{$id}/")
        ->setImageURI($project->getProfileImageURI());

      if ($project->getIsForRsp()) {
        if ($project->hasRspSpec()) {
          $spec = $project->getRspSpec();
          $icon_spec_icon = id(new PHUIIconView())
            ->setIcon('fa-money');
          $icon_spec_name = pht('Billed %d %s, Given %d %s',
            $spec->getStoryPointBilledValue(),
            $spec->getStoryPointCurrency(),
            $spec->getStoryPointValue(),
            $spec->getStoryPointCurrency());
        } else {
          $icon_spec_icon = id(new PHUIIconView())
            ->setIcon('fa-exclamation');
          $icon_spec_name = 'Spec missing';
          // $item->addIcon('fa-exclamation', pht('Spec Missing'));
        }

        $item->addAttribute(
          array(
            $icon_spec_icon,
            ' ',
            $icon_spec_name,
          ));
      }

      if ($project->getStatus() == PhabricatorProjectStatus::STATUS_ARCHIVED) {
        $item->addIcon('fa-ban', pht('Archived'));
        $item->setDisabled(true);
      }

      if ($this->showMember) {
        $is_member = $project->isUserMember($viewer_phid);
        if ($is_member) {
          $item->addIcon('fa-user', pht('Member'));
        }
      }



      if ($this->showWatching) {
        $is_watcher = $project->isUserWatcher($viewer_phid);
        if ($is_watcher) {
          $item->addIcon('fa-eye', pht('Watching'));
        }
      }

      $subtype = $project->newSubtypeObject();
      if ($subtype && $subtype->hasTagView()) {
        $subtype_tag = $subtype->newTagView()
          ->setSlimShady(true);
        $item->addAttribute($subtype_tag);
      }

      $project_id = $project->getID();
      if ($project->getIsForRsp()) {
        $item->addIcon('fa-wifi', pht('RSP'));
        $item->addAction(
          id(new PHUIListItemView())
            ->setIcon('fa-wrench')
            ->setName(pht('Edit Spec'))
            ->setWorkflow(true)
            ->setHref('/project/spec/'.$project_id.'/?via=suite'));

      }

      $item->addAction(
        id(new PHUIListItemView())
          ->setIcon($project->getIsForRsp()
                    ? 'fa-ban'
                    : 'fa-check')
          ->setName($project->getIsForRsp()
                    ? pht('Disable RSP')
                    : pht('Enable RSP'))
          ->setWorkflow(true)
          ->setHref($this->baseUri.'/disable/'.$project_id.'/'));

      $list->addItem($item);
    }

    return $list;
  }

  public function render() {
    return $this->renderList();
  }

}
