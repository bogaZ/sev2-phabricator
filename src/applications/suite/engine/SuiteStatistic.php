<?php

final class SuiteStatistic {

/* -(  buildXStats  )----------------------------------------- */
  public static function buildAllStats(PhabricatorUser $viewer) {
    $upper = id(new AphrontMultiColumnView())
      ->setFluidLayout(true);

    /* Action Panels */

    $upper->addColumn(self::buildActiveSubsriberPanel());
    $upper->addColumn(self::buildRspPanel());
    $upper->addColumn(self::buildOrgPanel());
    $upper->addColumn(self::buildProjectPanel());

    $bottom = id(new AphrontMultiColumnView())
      ->setFluidLayout(true);
    $bottom->addColumn(self::buildJobsPanel());
    $bottom->addColumn(self::buildRevisionPanel());
    $bottom->addColumn(self::buildEnrollmentPanel());
    $bottom->addColumn(self::buildSubmissionsPanel());

    $upper = phutil_tag_div('mlb', $upper);

    return phutil_tag_div('ml', array($upper, $bottom));
  }

  public static function buildDashboardStats(PhabricatorUser $viewer) {
    $view = id(new AphrontMultiColumnView())
      ->setFluidLayout(true);

    /* Action Panels */

    $view->addColumn(self::buildActiveSubsriberPanel());
    $view->addColumn(self::buildRspPanel());
    $view->addColumn(self::buildOrgPanel());
    $view->addColumn(self::buildProjectPanel());

    $view = phutil_tag_div('mlb', $view);

    return phutil_tag_div('ml', array($view));
  }

/* -(  buildXPanel  )----------------------------------------- */

  protected static function buildActiveSubsriberPanel() {
    return id(new PHUIActionPanelView())
      ->setIcon('fa-bolt')
      ->setBigText(true)
      ->setHeader(pht('Active Subscribers'))
      ->setStatus('available')
      ->setHref('#')
      ->setSubHeader(self::getTotalSubscribers())
      ->setState(PHUIActionPanelView::COLOR_YELLOW);
  }


  protected static function buildRspPanel() {
    return id(new PHUIActionPanelView())
      ->setIcon('fa-wifi')
      ->setBigText(true)
      ->setHeader(pht('RSP'))
      ->setHref('#')
      ->setSubHeader(self::getTotalRsp())
      ->setState(PHUIActionPanelView::COLOR_GREEN);
  }

  protected static function buildOrgPanel() {
    return id(new PHUIActionPanelView())
      ->setIcon('fa-building')
      ->setBigText(true)
      ->setHeader(pht('Partners'))
      ->setHref('#')
      ->setSubHeader(self::getTotalOrgs())
      ->setState(PHUIActionPanelView::COLOR_INDIGO);
  }

  protected static function buildProjectPanel() {
    return id(new PHUIActionPanelView())
      ->setIcon('fa-briefcase')
      ->setBigText(true)
      ->setHeader(pht('Projects'))
      ->setHref('#')
      ->setSubHeader(self::getTotalProjects())
      ->setState(PHUIActionPanelView::COLOR_RED);
  }

  protected static function buildJobsPanel() {
    return id(new PHUIActionPanelView())
      ->setIcon('fa-thumb-tack')
      ->setBigText(true)
      ->setHeader(pht('Job Postings'))
      ->setHref('#')
      ->setSubHeader(self::getTotalActiveJobs())
      ->setState(PHUIActionPanelView::COLOR_PINK);
  }

  protected static function buildRevisionPanel() {
    return id(new PHUIActionPanelView())
      ->setIcon('fa-gear')
      ->setBigText(true)
      ->setHeader(pht('RSP Revisions'))
      ->setHref('#')
      ->setSubHeader(self::getTotalRSPRevisions())
      ->setState(PHUIActionPanelView::COLOR_BLUE);
  }

  protected static function buildSubmissionsPanel() {
    return id(new PHUIActionPanelView())
      ->setIcon('fa-clone')
      ->setBigText(true)
      ->setHeader(pht('Test Submission'))
      ->setHref('#')
      ->setSubHeader(self::getTotalSubmissions())
      ->setState(PHUIActionPanelView::COLOR_VIOLET);
  }

  protected static function buildEnrollmentPanel() {
    return id(new PHUIActionPanelView())
      ->setIcon('fa-road')
      ->setBigText(true)
      ->setHeader(pht('Coursepath Enrollment'))
      ->setHref('#')
      ->setSubHeader(self::getTotalEnrollments())
      ->setState(PHUIActionPanelView::COLOR_ORANGE);
  }

/* -(  getX  )----------------------------------------- */

  /**
   * Get the total numbers of active PhabricatorUser
   * with active suite flag and subscription
   * @return int
   */
  protected static function getTotalSubscribers() {
    $table  = new PhabricatorUser();
    $conn_r = $table->establishConnection('r');

    $count = queryfx_all(
      $conn_r,
      'SELECT COUNT(*) FROM %T WHERE isSuite=1 '
      .'AND isSuiteSubscribed=1 AND isForDev=0',
      $table->getTableName());

    return $count;
  }


  /**
   * Get the total numbers of active SuiteProfile with isRsp on
   * @return int
   */
  protected static function getTotalRsp() {
    $table  = new SuiteProfile();
    $conn_r = $table->establishConnection('r');

    $count = queryfx_all(
      $conn_r,
      'SELECT COUNT(*) FROM %T WHERE isRsp=1',
      $table->getTableName());

    return $count;
  }

  /**
   * Get the total numbers of orgs
   * @return int
   */
  protected static function getTotalOrgs() {
    $table  = new PhabricatorProject();
    $conn_r = $table->establishConnection('r');

    $count = queryfx_all(
      $conn_r,
      'SELECT COUNT(*) FROM %T WHERE icon="organization" AND isForDev=0',
      $table->getTableName());

    return $count;

  }

  /**
   * Get the total numbers of RSP enabled projects
   * @return int
   */
  protected static function getTotalProjects() {
    return 0;
  }

  /**
   * Get the total numbers of active jobs
   * @return int
   */
  protected static function getTotalActiveJobs() {
    $table  = new JobPosting();
    $conn_r = $table->establishConnection('r');

    $count = queryfx_all(
      $conn_r,
      'SELECT COUNT(*) FROM %T WHERE isCancelled=0 AND isLead=0',
      $table->getTableName());

    return $count;
  }

  /**
   * Get the total numbers RSP revisions
   * @return int
   */
  protected static function getTotalRSPRevisions() {
    return 0;
  }

  /**
   * Get the total numbers of Enrollments
   * @return int
   */
  protected static function getTotalEnrollments() {
    return 0;
  }

  /**
   * Get the total numbers of submissions
   * @return int
   */
  protected static function getTotalSubmissions() {
    return 0;
  }
}
