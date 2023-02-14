<?php

final class DiffusionRepositoryBuildManagementPanel
  extends DiffusionRepositoryManagementPanel {

  protected $build;

  const PANELKEY = 'build';

  public function getBuild() {
    return $this->build;
  }

  public function setBuild($build) {
    $this->build = $build;
    return $this;
  }

  public function getManagementPanelLabel() {
    return pht('Info');
  }

  public function getManagementPanelOrder() {
    return 600;
  }

  public function getManagementPanelGroupKey() {
    return DiffusionRepositoryManagementBuildsPanelGroup::PANELGROUPKEY;
  }

  public function shouldEnableForRepository(
    PhabricatorRepository $repository) {
    return $repository->isGit();
  }

  protected function getEditEngineFieldKeys() {
    return array(
      'configuration',
    );
  }

  public function getManagementPanelIcon() {
    return 'fa-play';
  }

  public function buildManagementPanelCurtain() {
    $repository = $this->getRepository();
    $viewer = $this->getViewer();
    $action_list = $this->newActionList();
    $build = $this->getBuild();

    $can_file = false;
    $build_id = null;
    if ($build) {
      $can_file = true;
      $build_id = $build->getID();
    }

    $can_edit = PhabricatorPolicyFilter::hasCapability(
      $viewer,
      $repository,
      PhabricatorPolicyCapability::CAN_EDIT);

    $config_uri = $repository->getPathURI('edit/buildinfo/');
    $file_uri = $repository->getPathURI(
      "edit/buildinfo/$build_id/file/");

    $action_list->addAction(
      id(new PhabricatorActionView())
        ->setIcon('fa-pencil')
        ->setName(pht('Edit Configuration'))
        ->setHref($config_uri)
        ->setDisabled(!$can_edit)
        ->setWorkflow(!$can_edit));

    $action_list->addAction(
      id(new PhabricatorActionView())
        ->setIcon('fa-file')
        ->setName(pht('File Management'))
        ->setHref($file_uri)
        ->setDisabled(!$can_file)
        ->setWorkflow(!$can_edit));

    return $this->newCurtainView()
      ->setActionList($action_list);
  }

  public function buildManagementPanelContent() {
    $repository = $this->getRepository();
    $viewer = $this->getViewer();
    $build = id(new PhabricatorRepositoryBuildInfoQuery())
      ->setViewer($viewer)
      ->withRepositoryPHIDs(array($repository->getPHID()))
      ->executeOne();
    $this->setBuild($build);

    $view = id(new PHUIPropertyListView())
      ->setViewer($viewer);

    $config_icon = 'fa-times';
    $config_color = 'red';
    $config_label = pht('Not Configured');

    if ($build) {
      $config_icon = 'fa-check';
      $config_color = 'green';
      $config_label = pht('Configuration OK');
    }

    $config_view = id(new PHUIStatusListView())
      ->addItem(
        id(new PHUIStatusItemView())
          ->setIcon($config_icon, $config_color)
          ->setTarget($config_label));

    $view->addProperty(pht('build.json'), $config_view);

    return $this->newBox(pht('Build Info'), $view);
  }

}
