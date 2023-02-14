<?php

final class PhabricatorSuiteTransactionEditController
  extends PhabricatorSuiteController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {
    $rev_phid = 'PHID-DREV-yzxycqz6tjtt6tf2mrbx';

    $viewer = PhabricatorUser::getOmnipotentUser();
    $revision = id(new DifferentialRevisionQuery())
                ->withPHIDs(array($rev_phid))
                ->setViewer(PhabricatorUser::getOmnipotentUser())
                ->executeOne();

    $task_phids = PhabricatorEdgeQuery::loadDestinationPHIDs(
      $revision->getPHID(),
      DifferentialRevisionHasTaskEdgeType::EDGECONST);

    if ($task_phids) {
      $task = id(new ManiphestTaskQuery())
                ->setViewer($viewer)
                ->needProjectPHIDs(true)
                ->withPHIDs($task_phids)
                ->executeOne();

      $project_phids = $task->getProjectPHIDs();
      $project_phid = head($project_phids);


      PhabricatorWorker::setRunAllTasksInProcess(true);
      PhabricatorWorker::scheduleTask(
        'SuiteBillStoryPointWorker',
        array(
          'storyPoint' => $task->getPoints(),
          'revisionPHID' => $revision->getPHID(),
          'projectPHID' => $project_phid,
        ));
      exit('OK');
    }

    exit('NOT OK');
  }

  protected function requiresManageBilingCapability() {
    return true;
  }

  protected function requiresManageSubscriptionCapability() {
    return true;
  }

  protected function requiresManageUserCapability() {
    return false;
  }

}
