<?php

final class PhabricatorSuiteInvitesSentController
  extends PhabricatorSuiteController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {

    $viewer = $this->getViewer();
    $title = 'Invited Emails';
    $header = $this->buildHeaderView();

    // Main Info

    $main_info_box = id(new PHUIObjectBoxView())
      ->setHeaderText(pht('Sent Invitations'))
      ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
      ->appendChild($this->buildSentTable());

    $view = id(new PHUITwoColumnView())
      ->setHeader($header)
      ->setMainColumn(array(
        $main_info_box
        ));

    $crumbs = $this->buildApplicationCrumbs();
    return $this->newPage()
      ->setTitle($title)
      ->setCrumbs($crumbs)
      ->appendChild($view);

  }

  protected function buildApplicationCrumbs() {
    $paths_uri = $this->getApplicationURI('/invites');
    $sent_uri = $this->getApplicationURI('/invites/sent');

    $crumbs = parent::buildApplicationCrumbs();
    $crumbs->addTextCrumb('Invites', $paths_uri);
    $crumbs->addTextCrumb('Sent', $sent_uri);
    $crumbs->setBorder(true);
    return $crumbs;
  }

  protected function buildHeaderView() {
    $viewer = $this->getViewer();
    $status_icon = 'fa-envelope';

    $header = id(new PHUIHeaderView())
      ->setHeader('Sent')
      ->setHeaderIcon($status_icon)
      ->setUser($viewer);

    return $header;

  }

  protected function buildSentTable() {
    $rows = array();

    $mail_mta_dao = new PhabricatorMetaMTAMail();
    $sent = $mail_mta_dao->loadAllWhere('parameters LIKE %~', 'suite_invite');

    foreach($sent as $entry) {
      $params = $entry->getParameters();
      $rawTos = $params['raw-to'];
      foreach($rawTos as $to) {
        $rows[] = array(
          phabricator_dual_datetime(
            $entry->getDateCreated(),
            $this->getViewer()),
          $to,
          $entry->getStatus(),
        );
      }
    }

    $table = id(new AphrontTableView($rows))
      ->setNoDataString(pht('No invitations.'))
      ->setHeaders(
        array(
          pht('Date'),
          pht('Email'),
          pht('Status'),
        ))
      ->setColumnClasses(
        array(
          'pri',
          ' ',
          'right',
          'right',
          'right',
          'right',
        ));

    $notice = pht('All invitations');
    $table->setNotice($notice);

    return $table;
  }

  protected function requiresManageBilingCapability() {
    return false;
  }

  protected function requiresManageSubscriptionCapability() {
    return false;
  }

  protected function requiresManageUserCapability() {
    return true;
  }

}
