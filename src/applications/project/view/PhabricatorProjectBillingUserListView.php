<?php

final class PhabricatorProjectBillingUserListView
  extends PhabricatorProjectUserListView {

  protected function canEditList() {
    return false;
  }

  protected function getNoDataString() {
    return pht('This project does not have any billing user.');
  }

  protected function getRemoveURI($phid) {
    return null;
  }

  protected function getHeaderText() {
    return pht('Billing Users');
  }

  protected function getMembershipNote() {
    $viewer = $this->getViewer();
    $viewer_phid = $viewer->getPHID();
    $project = $this->getProject();

    if (!$viewer_phid) {
      return null;
    }

    $note = null;
    if ($project->isUserMember($viewer_phid)) {
      $note = pht(
        'You are a billing user, when this project gets '.
        'billed you will be charged.');
    }

    return $note;
  }

}
