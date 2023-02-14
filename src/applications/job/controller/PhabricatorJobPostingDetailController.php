<?php

abstract class PhabricatorJobPostingDetailController
  extends PhabricatorController {

  private $item;

  public function setItem(JobPosting $item) {
    $this->item = $item;
    return $this;
  }

  public function getItem() {
    return $this->item;
  }

  public function buildApplicationMenu() {
    return $this->buildSideNavView()->getMenu();
  }

  protected function buildHeaderView() {
    $viewer = $this->getViewer();
    $item = $this->getItem();
    $id = $item->getID();

    if ($item->getIsCancelled()) {
      $status_icon = 'fa-ban';
      $status_color = 'dark';
    } else {
      $status_icon = 'fa-check';
      $status_color = 'bluegrey';
    }

    $status_name = idx(
      JobPosting::getStatusNameMap(),
      $item->getIsLead());
    $period = $this->buildPeriodTitle($item);
    $salary = id(new PHUITagView())
                ->setIcon('fa-money')
                ->setType(PHUITagView::TYPE_OBJECT)
                ->setName(pht(
                  '%s %d - %d',
                  $item->getSalaryCurrency(),
                  $item->getSalaryFrom(),
                  $item->getSalaryTo()
                ));
    $location = id(new PHUITagView())
              ->setIcon('fa-location-arrow')
              ->setType(PHUITagView::TYPE_OBJECT)
              ->setName(pht(
                '%s',
                $item->getLocation()));
    $header = id(new PHUIHeaderView())
      ->setHeader($item->getName())
      ->setHeaderIcon($item->getIcon())
      ->setSubheader($period)
      ->addTag($location)
      ->addTag($salary)
      ->setUser($viewer)
      ->setPolicyObject($item)
      ->setStatus($status_icon, $status_color, $status_name);

   if ($item->hasTechStack()) {
    $course = id(new PHUITagView())
                ->setIcon('fa-road')
                ->setColor('pink')
                ->setType(PHUITagView::TYPE_OBJECT)
                ->setName(id(new CoursepathItemQuery())
                            ->setViewer($viewer)
                            ->withPHIDs(array(
                              $item->getTechStack()->getCoursepathItemPHID(),
                            ))
                            ->executeOne()
                            ->getName());
    $header->addTag($course);
   }

    // Set is lead
    $options = JobPosting::getStatusMap();

    $selected = $item->getIsLead()
        ? JobPosting::STATUS_LEAD
        : JobPosting::STATUS_LEGIT;

    $selected_option = idx($options, $selected);

    $availability_select = id(new PHUIButtonView())
      ->setTag('a')
      ->setIcon('fa-circle '.$selected_option['color'])
      ->setText(pht('State: %s', $selected_option['name']));

    $dropdown = id(new PhabricatorActionListView())
      ->setUser($viewer);

    foreach ($options as $key => $option) {
      $uri = "status/{$id}/{$key}/";
      $uri = $this->getApplicationURI($uri);

      $dropdown->addAction(
        id(new PhabricatorActionView())
          ->setName($option['name'])
          ->setIcon('fa-circle '.$option['color'])
          ->setHref($uri)
          ->setWorkflow(true));
    }

    $tech_stack_action = $this->buildTechStack($item);
    $header->addActionLink($tech_stack_action);

    $availability_select->setDropdownMenu($dropdown);
    $header->addActionLink($availability_select);

    return $header;
  }

  protected function buildApplicationCrumbs() {
    $item = $this->getItem();
    $id = $item->getID();
    $paths_uri = $this->getApplicationURI("/");
    $item_uri = $this->getApplicationURI("/view/{$id}/");

    $crumbs = parent::buildApplicationCrumbs();
    $crumbs->addTextCrumb("Upcoming Jobs", $paths_uri);
    $crumbs->addTextCrumb($item->getName(), $item_uri);
    $crumbs->setBorder(true);
    return $crumbs;
  }

  protected function buildSideNavView($filter = null) {
    $viewer = $this->getViewer();
    $item = $this->getItem();
    $id = $item->getID();

    $can_edit = PhabricatorPolicyFilter::hasCapability(
      $viewer,
      $item,
      PhabricatorPolicyCapability::CAN_EDIT);

    $nav = id(new AphrontSideNavFilterView())
      ->setBaseURI(new PhutilURI($this->getApplicationURI()));

    $nav->addLabel(pht('Job Posting'));

    $nav->addFilter(
      'view',
      pht('Job Detail'),
      $this->getApplicationURI("/view/{$id}/"),
      'fa-thumb-tack');

    $nav->addFilter(
      'applicants',
      pht('Applicants'),
      $this->getApplicationURI("/view/{$id}/applicants"),
      'fa-group');

    $nav->selectFilter($filter);

    return $nav;
  }

  private function buildPeriodTitle($item) {
    $epoch = $item->getEndDateTimeEpoch();
    $age = $epoch - time();
    $age = floor($age / (60 * 60 * 24));
    if ($age < 1) {
      $when = pht('Today');
    } else if ($age == 1) {
      $when = pht('Tomorrow');
    } else {
      $when = pht('%s Day(s)', new PhutilNumber($age));
    }

    return pht('Expired in %s', $when);
  }

  protected function buildTechStack(JobPosting $job) {
    $viewer = $this->getViewer();
    $id = $job->getID();

    $can_edit = PhabricatorPolicyFilter::hasCapability(
      $viewer,
      $job,
      PhabricatorPolicyCapability::CAN_EDIT);

    $tech_stack_icon = 'fa-wrench';
    $tech_stack_text = pht('Job Tech Stack');
    $tech_stack_href = "/job/techstack/{$id}/";
    $tech_stack_disabled = true;

    $tech_stack_icon = id(new PHUIIconView())
      ->setIcon($tech_stack_icon);

    return id(new PHUIButtonView())
      ->setTag('a')
      ->setWorkflow(true)
      ->setIcon($tech_stack_icon)
      ->setText($tech_stack_text)
      ->setHref($tech_stack_href)
      ->setDisabled(!$can_edit);
  }

}
