<?php

final class PhabricatorSuiteInvitesController
  extends PhabricatorSuiteController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {

    $viewer = $this->getViewer();
    $title = 'Invite Users';
    $header = $this->buildHeaderView();
    $done_uri = $this->getApplicationURI('invites/sent');

    if ($request->isFormPost()) {
      $emails = $request->getStr('email_addreses');
      $tos = explode(',', $emails);

      $mail = id(new PhabricatorMetaMTAMail())
        ->addRawTos($tos)
        ->setForceDelivery(true)
        ->setMailTags(array('suite_invite' => true))
        ->setSubject(
          pht(
            '[Suite] %s telah mengundang anda menjadi yang pertama',
            'Refactory'))
        ->setBody("Hey,\n".
        "Kami akhirnya merampungkan platform kami, Suite - dan kami ingin ".
        " mengajak anda untuk menjadi yang pertama untuk mencobanya!\n\n".
        "Di platform ini anda dapat menjadi RSP maupun mengikuti program".
        " hiring kami. Anda dapat mendownload Suite melalui link berikut\n\n".
        "https://suite.refactory.id/\n\n".
        "Kami berharap dapat segera bertemu anda disana!\n".
        "- Refactory")
        ->saveAndSend();

      return id(new AphrontRedirectResponse())->setURI($done_uri);
    }

    // Main Info
    $invite_form = id(new AphrontFormView())
      ->setUser($viewer)
      ->appendRemarkupInstructions(
        pht(
          'Enter email addresses separated by commas ' ))
      ->appendControl(
        id(new AphrontFormTextAreaControl())
          ->setLabel(pht('Email Addresses'))
          ->setName('email_addreses'))
      ->appendChild(
        id(new AphrontFormSubmitControl())
          ->addCancelButton($done_uri)
          ->setValue(pht('Send')));

    $main_info_box = id(new PHUIObjectBoxView())
      ->setHeaderText(pht('Send email invitation'))
      ->setBackground(PHUIObjectBoxView::BLUE_PROPERTY)
      ->setForm($invite_form);

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

    $crumbs = parent::buildApplicationCrumbs();
    $crumbs->addTextCrumb('Invites', $paths_uri);
    $crumbs->setBorder(true);
    return $crumbs;
  }

  protected function buildHeaderView() {
    $viewer = $this->getViewer();
    $status_icon = 'fa-paper-plane';

    $header = id(new PHUIHeaderView())
      ->setHeader('Invite users')
      ->setHeaderIcon($status_icon)
      ->setUser($viewer);

    $header->addActionLink($this->buildSentButton());

    return $header;
  }

  protected function buildSentButton() {
    $viewer = $this->getViewer();
    $icon = 'fa-envelope';
    $text = pht('Sent Invitations');
    $href = '/suite/invites/sent';

    $icon = id(new PHUIIconView())
      ->setIcon($icon);

    return id(new PHUIButtonView())
      ->setTag('a')
      ->setWorkflow(false)
      ->setIcon($icon)
      ->setText($text)
      ->setHref($href);
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
