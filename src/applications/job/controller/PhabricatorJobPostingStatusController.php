<?php

final class PhabricatorJobPostingStatusController
  extends PhabricatorJobPostingController {

  public function handleRequest(AphrontRequest $request) {
    $viewer = $this->getViewer();
    $id = $request->getURIData('id');

    $posting = id(new JobPostingQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->executeOne();
    if (!$posting) {
      return new Aphront404Response();
    }

    $cancel_uri = $posting->getViewURI();

    // TODO: This endpoint currently only works via AJAX. It would be vaguely
    // nice to provide a plain HTML version of the workflow where we return
    // a dialog with a vanilla <select /> in it for cases where all the JS
    // breaks.
    $request->validateCSRF();

    $map = JobPosting::getStatusMap();
    $new_status = $request->getURIData('status');
    if (isset($map[$new_status])) {
      $posting
        ->setIsLead($new_status == JobPosting::STATUS_LEAD ? 1 : 0)
        ->save();
    }

    return id(new AphrontRedirectResponse())->setURI($cancel_uri);
  }
}
