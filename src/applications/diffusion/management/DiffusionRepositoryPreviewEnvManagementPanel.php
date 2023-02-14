<?php

final class DiffusionRepositoryPreviewEnvManagementPanel
  extends DiffusionRepositoryManagementPanel {

  const PANELKEY = 'previewenv';

  public function getManagementPanelLabel() {
    return pht('Preview Environment');
  }

  public function getManagementPanelOrder() {
    return 900;
  }

  public function getManagementPanelGroupKey() {
    return DiffusionRepositoryManagementBuildsPanelGroup::PANELGROUPKEY;
  }

  public function shouldEnableForRepository(
    PhabricatorRepository $repository) {
    return $repository->isGit();
  }

  public function getManagementPanelIcon() {
    $repository = $this->getRepository();

    $nomad_profile = $repository->getNomadHost();

    if ($nomad_profile) {
      return 'fa-cubes';
    } else {
      return 'fa-cubes grey';
    }
  }

  public function buildManagementPanelCurtain() {
    $repository = $this->getRepository();
    $viewer = $this->getViewer();
    $action_list = $this->newActionList();

    $can_edit = PhabricatorPolicyFilter::hasCapability(
      $viewer,
      $repository,
      PhabricatorPolicyCapability::CAN_EDIT);

    $nomad_profile = $repository->getPathURI(
      'edit/nomad_profile/');
    $nomad_job = $repository->getPathURI(
      'edit/nomad_job/');

    $action_list->addAction(
      id(new PhabricatorActionView())
        ->setIcon('fa-pencil')
        ->setName(pht('Edit Nomad Profile'))
        ->setHref($nomad_profile)
        ->setDisabled(!$can_edit)
        ->setWorkflow(true));

    $action_list->addAction(
      id(new PhabricatorActionView())
        ->setIcon('fa-cube')
        ->setName(pht('Edit Nomad Job'))
        ->setWorkflow(true)
        ->setDisabled(!$can_edit)
        ->setHref($nomad_job));

    return $this->newCurtainView()
      ->setActionList($action_list);
  }

  public function buildManagementPanelContent() {
    $repository = $this->getRepository();
    $viewer = $this->getViewer();

    $view = id(new PHUIPropertyListView())
      ->setViewer($viewer);

    $dict = $repository->toDictionary();

    $nomad_profile = null;
    if ($dict['nomad']['region']) {
      $nomad_profile = $dict['nomad']['host'].$dict['nomad']['region'];
    }
    if (!$nomad_profile) {
      $nomad_profile = phutil_tag('em', array(), pht('No selected profile'));
    }

    $nomad_job = null;
    if ($dict['nomad']['job']) {
      $nomad_job = $dict['nomad']['job'];
    }
    if (!$nomad_job) {
      $nomad_job = phutil_tag('em', array(), pht('No specified job'));
    }

    $view->addProperty(pht('Nomad Profile'), $nomad_profile);
    $view->addProperty(pht('Nomad Job'), $nomad_job);

    return $this->newBox(pht('Preview Environment'), $view);
  }

}
