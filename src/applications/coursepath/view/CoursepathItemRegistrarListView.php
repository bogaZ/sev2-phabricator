<?php

final class CoursepathItemRegistrarListView extends AphrontView {

  private $item;
  private $enrollments;
  private $handles;

  public function setItem(CoursepathItem $item) {
    $this->item = $item;
    return $this;
  }

  public function setEnrollments(array $enrollments) {
    $this->enrollments = $enrollments;
    return $this;
  }

  public function setHandles(array $handles) {
    $this->handles = $handles;
    return $this;
  }

  public function render() {
    $viewer = $this->getViewer();

    $item = $this->item;
    $handles = $this->handles;
    $enrollments = mpull($this->enrollments, null, 'getRegistrarPHID');

    $can_edit = PhabricatorPolicyFilter::hasCapability(
      $viewer,
      $item,
      PhabricatorPolicyCapability::CAN_EDIT);

    $enroll_button = id(new PHUIButtonView())
      ->setTag('a')
      ->setIcon('fa-plus')
      ->setText(pht('Add Registrars'))
      ->setWorkflow(true)
      ->setDisabled(!$can_edit)
      ->setHref('enroll/');

    $header = id(new PHUIHeaderView())
      ->setHeader(pht('Registrars'))
      ->addActionLink($enroll_button);

    $list = id(new PHUIObjectItemListView())
      ->setNoDataString(pht('This item does not have any registrars.'))
      ->setFlush(true);

    foreach ($handles as $handle) {
      $remove_uri = 'unenroll/?phid='.$handle->getPHID();

      $enroll = $enrollments[$handle->getPHID()];
      $tutor_handle = $viewer->renderHandle($enroll->getTutorPHID());
      $enroll_date = phabricator_date($enroll->getDateCreated(), $viewer);
      $tutor_info = pht(
        'Enrolled by %s on %s',
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
