<?php

final class DifferentialPreviewEnvField
  extends DifferentialCustomField {

  public function getFieldKey() {
    return 'differential:previewenv';
  }

  public function getFieldName() {
    return pht('Preview Environment');
  }

  public function getFieldDescription() {
    return pht('Shows corresponding nomad allocation.');
  }

  public function shouldAppearInPropertyView() {
    return true;
  }

  public function renderPropertyViewValue(array $handles) {
    return null;
  }

  public function shouldAppearInDiffPropertyView() {
    return true;
  }

  public function renderDiffPropertyViewLabel(DifferentialDiff $diff) {
    return $this->getFieldName();
  }

  public function getWarningsForDetailView() {
    $warnings = array();

    return $warnings;
  }

  public function renderDiffPropertyViewValue(DifferentialDiff $diff) {

    $colors = array(
      DifferentialUnitStatus::UNIT_NONE => 'grey',
      DifferentialUnitStatus::UNIT_OKAY => 'green',
      DifferentialUnitStatus::UNIT_WARN => 'yellow',
      DifferentialUnitStatus::UNIT_FAIL => 'red',
      DifferentialUnitStatus::UNIT_SKIP => 'blue',
      DifferentialUnitStatus::UNIT_AUTO_SKIP => 'blue',
    );
    $icon_color = 'green';//idx($colors, $diff->getUnitStatus(), 'grey');

    if (!$diff->getRepositoryPHID()) {
      // If there is no repo, there is no point to continue
      return null;
    }

    $task_phids = PhabricatorEdgeQuery::loadDestinationPHIDs(
      $diff->getRevision()->getPHID(),
      DifferentialRevisionHasTaskEdgeType::EDGECONST);

    if (empty($task_phids)) {
      // If there is no task, there is no point to continue
      return null;
    }

    // Get task
    $phid = head($task_phids);
    $task = id(new ManiphestTaskQuery())
              ->withPHIDs(array($phid))
              ->needProjectPHIDs(true)
              ->setViewer(PhabricatorUser::getOmnipotentUser())
              ->executeOne();

    // Get repo
    $query = id(new PhabricatorRepositoryQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withPHIDs(array($diff->getRepositoryPHID()))
      ->needProfileImage(true)
      ->needURIs(true);

    $repository = $query->executeOne();

    if ($repository->getNomadJob() &&
        $repository->getNomadRegion()) {
      $region = $repository->getNomadRegion();
      $author_phid = $diff->getAuthorPHID();


      $status = id(new PHUIStatusListView())
        ->addItem(
          id(new PHUIStatusItemView())
            ->setIcon('fa-cube', $icon_color)
            ->setTarget(phutil_tag('a',
                array('href' => pht('https://%s%s.%s.refactory.id/',
                $task->getMonogram(), $author_phid, $region)),
                pht('https://%s%s.%s.refactory.id/',
                $task->getMonogram(), $author_phid, $region))));

      return $status;
    }
  }
}
