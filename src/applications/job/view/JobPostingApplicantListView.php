<?php

final class JobPostingApplicantListView
  extends AphrontView {

  private $posting;
  private $applicants;
  private $handles;

  public function setPosting(JobPosting $posting) {
    $this->posting = $posting;
    return $this;
  }
  public function setApplicants(array $applicants) {
    $this->applicants = $applicants;
    return $this;
  }

  public function setHandles(array $handles) {
    $this->handles = $handles;
    return $this;
  }

  public function render() {
    $viewer = $this->getViewer();

    $item = $this->posting;
    $handles = $this->handles;
    $applicants = mpull($this->applicants, null, 'getApplicantPHID');

    $can_edit = PhabricatorPolicyFilter::hasCapability(
      $viewer,
      $item,
      PhabricatorPolicyCapability::CAN_EDIT);

    $enroll_button = id(new PHUIButtonView())
      ->setTag('a')
      ->setIcon('fa-plus')
      ->setText(pht('Invite applicant'))
      ->setWorkflow(true)
      ->setDisabled(!$can_edit)
      ->setHref('apply/');

    $header = id(new PHUIHeaderView())
      ->setHeader(pht('Applicants'))
      ->addActionLink($enroll_button);

    $list = id(new PHUIObjectItemListView())
      ->setNoDataString(pht('This posting does not have any applicants.'))
      ->setFlush(true);

    foreach ($handles as $handle) {
      $remove_uri = 'retract/?phid='.$handle->getPHID();

      $enroll = $applicants[$handle->getPHID()];
      $tutor_handle = $viewer->renderHandle($enroll->getInviterPHID());
      $enroll_date = phabricator_date($enroll->getDateCreated(), $viewer);
      $tutor_info = pht(
        'Applied by %s on %s',
        $tutor_handle->render(),
        $enroll_date);

      $item = id(new PHUIObjectItemView())
        ->setHeader($handle->getFullName())
        ->setSubhead($tutor_info)
        ->setHref($handle->getURI())
        ->setImageURI($handle->getImageURI());

      if ($can_edit) {
        $item->addAction(
          id(new PHUIListItemView())
            ->setIcon('fa-times')
            ->setName(pht('Remove'))
            ->setHref($remove_uri)
            ->setWorkflow(true));
      }

      $list->addItem($item);
    }

    $box = id(new PHUIObjectBoxView())
      ->setHeader($header)
      ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
      ->setObjectList($list);

    return $box;
  }
}
