<?php

final class DifferentialRspEnabledField
  extends DifferentialCoreCustomField {

  public function getFieldKey() {
    return 'differential:project-rsp-enabled';
  }

  public function canDisableField() {
    return false;
  }

  public function getFieldName() {
    return pht('RSP Project');
  }

  public function getFieldDescription() {
    return pht('Lists associated RSP enabled projects.');
  }

  public function shouldAppearInPropertyView() {
    return true;
  }

  public function renderPropertyViewLabel() {
    return $this->getFieldName();
  }

  protected function readValueFromRevision(DifferentialRevision $revision) {
    if (!$revision->getPHID()) {
      return array();
    }

    $task_phids = PhabricatorEdgeQuery::loadDestinationPHIDs(
      $revision->getPHID(),
      DifferentialRevisionHasTaskEdgeType::EDGECONST);

    if (empty($task_phids)) {
      return array('projects' => array(), 'revision' => $revision);
    }

    $tasks = id(new ManiphestTaskQuery())
              ->withPHIDs($task_phids)
              ->needProjectPHIDs(true)
              ->setViewer(PhabricatorUser::getOmnipotentUser())
              ->execute();

    $project_phids = call_user_func_array(
      'array_merge', mpull($tasks, 'getProjectPHIDs'));
    $projects = id(new PhabricatorProjectQuery())
              ->withPHIDs($project_phids)
              ->needRspSpec(true)
              ->setViewer(PhabricatorUser::getOmnipotentUser())
              ->execute();

    $project_phids_with_spec = array();
    foreach ($projects as $project) {
      if ($project->getIsForRsp()
          && $project->getRspSpec()) {
          $project_phids_with_spec[] = $project->getPHID();
      }
    }

    return array(
      'projects' => $project_phids_with_spec,
      'revision' => $revision,
    );
  }

  public function getRequiredHandlePHIDsForPropertyView() {
    $vals = $this->getValue();
    return $vals['projects'];
  }

  public function renderPropertyViewValue(array $handles) {
    return $this->renderHandleList($handles);
  }

  public function getWarningsForDetailView() {
    $warnings = array();

    $vals = $this->getValue();
    $rsp_enabled_projects = $vals['projects'];
    $revision = $vals['revision'];
    if (count($rsp_enabled_projects) > 0
        && in_array($this->getViewer()->getPHID(),
        $revision->getReviewerPHIDs())) {
      $projects = id(new PhabricatorProjectQuery())
                ->withPHIDs($rsp_enabled_projects)
                ->needRspSpec(true)
                ->setViewer(PhabricatorUser::getOmnipotentUser())
                ->execute();
      $project = head($projects);
      $project_name = $project->getName();
      $spec = $project->getRspSpec();
      $sp = pht('%s %s', $spec->getStoryPointCurrency(),
        $spec->getStoryPointValue());
      $warnings[] = new PhutilSafeHTML(
        'This revision is linked to <b>RSP enabled</b> project.'.
        ' That means on accepted action, we will '.
        '<ol>'.
        '<li>1. <b>Charge billing user of '.$project_name.'</b></li>'.
        '<li>2. <b>Grant '.$sp.' for each '.
        'story point to revision author</b></li>'.
        '</ol>'.
        'So be sure to double check prior accepting the revision'
      );
    }
    return $warnings;
  }

}
