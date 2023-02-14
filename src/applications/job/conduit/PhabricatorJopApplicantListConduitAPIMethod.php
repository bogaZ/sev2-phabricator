<?php

final class PhabricatorJopApplicantListConduitAPIMethod
  extends PhabricatorJobConduitAPIMethod {

  public function getAPIMethodName() {
    return 'job.applicants.list';
  }

  public function getMethodDescription() {
    return pht("List Job's Applicants");
  }

  public function getMethodSummary() {
    return pht("List Job's Applicants");
  }

  protected function defineParamTypes() {
    return array(
      'projectPHID'             => 'required string',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $request->getUser();
    $project_phid = $request->getValue('projectPHID');
    $job_phids = array();
    $data = array();
    $results = array();

    $edge_query = id(new PhabricatorEdgeQuery())
    ->withSourcePHIDs(array($project_phid))
    ->withEdgeTypes(
      array(
        PhabricatorProjectProjectHasObjectEdgeType::EDGECONST,
      ));

    $edge_query->execute();

    if ($edge_query) {
      $phids = $edge_query->getDestinationPHIDs(array($project_phid));
      foreach ($phids as $phid) {
        if (strpos($phid, 'JOBS') !== false) {
          $job_phids[] = $phid;
        }
      }
    }

    $human_dformat = 'd F Y';
    if ($job_phids) {
      $jobs = id(new JobPostingQuery())
        ->needApplicants(true)
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->withPHIDs($job_phids)
        ->execute();

      foreach ($jobs as $job) {
        $data['phid'] = $job->getPHID();
        $data['job_name'] = $job->getName();
        $data['is_lead'] = (bool)$job->getIsLead();

        $end_date_initial = phabricator_datetime(
          $job->getUtcUntilEpoch(),
          $viewer);

        $data['end_at'] = date($human_dformat, strtotime($end_date_initial));
        $data['total_applicants'] = count($job->getApplicants());
        $results[] = $data;
      }
    }

    return array(
      'data' => $results,
    );
  }
}
