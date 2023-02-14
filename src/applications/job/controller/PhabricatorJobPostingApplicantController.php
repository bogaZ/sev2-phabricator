<?php

final class PhabricatorJobPostingApplicantController
  extends PhabricatorJobPostingDetailController {

  public function shouldAllowPublic() {
    return true;
  }

  public function handleRequest(AphrontRequest $request) {

    $viewer = $request->getViewer();
    $id = $request->getURIData('id');

    $job = id(new JobPostingQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->needTechStack(true)
      ->executeOne();
    if (!$job) {
      return new Aphront404Response();
    }
    $this->setItem($job);
    $applicants = id(new JobInviteQuery())
      ->setViewer($viewer)
      ->withPostingPHIDs(array($job->getPHID()))
      ->execute();

    $applicant_phids = mpull($applicants, 'getApplicantPHID');
    $applicant_phids = array_reverse($applicant_phids);
    $handles = $this->loadViewerHandles($applicant_phids);

    $crumbs = $this->buildApplicationCrumbs();
    $crumbs->addTextCrumb(pht('Applicants'));
    $crumbs->setBorder(true);
    $title = $job->getName();

    $header = $this->buildHeaderView();

    $registrar_list = id(new JobPostingApplicantListView())
      ->setPosting($job)
      ->setApplicants($applicants)
      ->setHandles($handles)
      ->setUser($viewer);

    $view = id(new PHUITwoColumnView())
      ->setHeader($header)
      ->setFooter(array(
          $registrar_list,
        ));

    $navigation = $this->buildSideNavView('Applicants');

    return $this->newPage()
      ->setTitle($title)
      ->setCrumbs($crumbs)
      ->setPageObjectPHIDs(array($job->getPHID()))
      ->setNavigation($navigation)
      ->appendChild($view);
  }

}
