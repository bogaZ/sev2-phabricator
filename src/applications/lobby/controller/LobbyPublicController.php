<?php

final class LobbyPublicController
  extends PhabricatorController {

  protected $conpherence;


  public function shouldAllowPublic() {
    return true;
  }

  public function handleRequest(AphrontRequest $request) {
    require_celerity_resource('phabricator-lobby-conpherence-css');

    $workspace = PhabricatorEnv::getEnvConfig('sev2.workspace');
    $owner_email = PhabricatorEnv::getEnvConfig('sev2.admin-email');
    $title = pht('%s Public Space', $workspace);
    $admin = PhabricatorUser::getOmnipotentUser();

    $conpherences = id(new ConpherenceThreadQuery())
      ->setViewer($admin)
      ->withPublic(true)
      ->needProfileImage(true)
      ->needTransactions(true)
      ->execute();

    $conpherence = null;
    foreach ($conpherences as $c) {
      if ($c->getViewPolicy() == PhabricatorPolicies::POLICY_PUBLIC) {
        // This is new workspace
        $conpherence = $c;
        break;
      }
    }

    if (!$conpherence) {
      // If there is no channel, create one!

      // First, create our bot if its not exists
      $bots = id(new PhabricatorPeopleQuery())
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->withUsernames(array('sev2bot'))
        ->execute();

      if (count($bots) > 0) {
        $bot = head($bots);
      } else {
        $bot = new PhabricatorUser();
        $bot->setUsername('sev2bot');
        $bot->setRealname('SEV-2 BOT');
        $bot->setIsApproved(1);

        $email_object = id(new PhabricatorUserEmail())
          ->setAddress('bot@sev-2.com')
          ->setIsVerified(1);

        id(new PhabricatorUserEditor())
          ->setActor($admin)
          ->createNewUser($bot, $email_object);
      }

      // Now create the public channel
      $conpherence = id(new ConpherenceThread())
        ->setMessageCount(0)
        ->setIsHQ(0)
        ->setIsPublic(1)
        ->setTitle($title)
        ->setTopic(pht('Ruang Publik %s', $workspace))
        ->attachParticipants(array())
        ->setViewPolicy(PhabricatorPolicies::POLICY_PUBLIC)
        ->setEditPolicy(PhabricatorPolicies::POLICY_ADMIN)
        ->setJoinPolicy(PhabricatorPolicies::POLICY_USER)
        ->setIsDeleted(0);

        $xactions = array();
        $xactions[] = id(new ConpherenceTransaction())
          ->setTransactionType(
            ConpherenceThreadParticipantsTransaction::TRANSACTIONTYPE)
          ->setNewValue(array('+' => array($bot->getPHID())));

        id(new ConpherenceEditor())
          ->setActor($bot)
          ->setContentSource(PhabricatorContentSource::newForSource(
            SuiteContentSource::SOURCECONST))
          ->setContinueOnNoEffect(true)
          ->applyTransactions($conpherence, $xactions);

      $conpherences = id(new ConpherenceThreadQuery())
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->needProfileImage(true)
        ->needTransactions(true)
        ->execute();

      $conpherence = head($conpherences);
    }

    $query = id(new ConpherenceThreadQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withIDs(array($conpherence->getID()))
      ->needProfileImage(true)
      ->needTransactions(true)
      ->setTransactionLimit(50);

    $this->conpherence = $query->executeOne();
    $marker_type = 'older';

    $data = ConpherenceTransactionRenderer::renderTransactions(
      PhabricatorUser::getOmnipotentUser(),
      $conpherence,
      $marker_type);
    $messages = ConpherenceTransactionRenderer::renderMessagePaneContent(
      $data['transactions'],
      $data['oldest_transaction_id'],
      $data['newest_transaction_id']);

    $content = array(
      'transactions' => $messages,
    );

    $d_data = $conpherence->getDisplayData(
      PhabricatorUser::getOmnipotentUser());
    $content['title'] = $title = $d_data['title'];
    $theme = ConpherenceRoomSettings::COLOR_LIGHT;

    $layout = id(new ConpherenceLayoutView())
      ->setUser(PhabricatorUser::getOmnipotentUser())
      ->setBaseURI($this->getApplicationURI())
      ->setThread($conpherence)
      ->setHeader($this->buildHeaderPaneContent($conpherence))
      ->setMessages($messages)
      ->setTheme($theme)
      ->setLatestTransactionID($data['latest_transaction_id'])
      ->addClass('conpherence-no-pontificate')
      ->setRole('thread');

    return $this->newPage()
      ->setTitle($title)
      ->setPageObjectPHIDs(array($conpherence->getPHID()))
      ->appendChild($layout);

  }

  protected function buildHeaderPaneContent(
    ConpherenceThread $conpherence) {
    $viewer = $this->getViewer();

    $layouts = array();

    $header = null;
    $id = $conpherence->getID();

    if ($id) {
      $data = $conpherence->getDisplayData($this->getViewer());

      $header = id(new PHUIHeaderView())
        ->setViewer($viewer)
        ->setHeader($data['title'])
        ->setPolicyObject($conpherence)
        ->setImage($data['image']);

      if (strlen($data['topic'])) {
        $topic = id(new PHUITagView())
          ->setName($data['topic'])
          ->setColor(PHUITagView::COLOR_VIOLET)
          ->setType(PHUITagView::TYPE_SHADE)
          ->addClass('conpherence-header-topic');
        $header->addTag($topic);
      }

      $can_edit = PhabricatorPolicyFilter::hasCapability(
        $viewer,
        $conpherence,
        PhabricatorPolicyCapability::CAN_EDIT);

      if ($can_edit) {
        $header->setImageURL(
          $this->getApplicationURI("picture/{$id}/"));
      }

      $participating = $conpherence->getParticipantIfExists($viewer->getPHID());



      $widget_key = PhabricatorConpherenceWidgetVisibleSetting::SETTINGKEY;
      $widget_view = (bool)$viewer->getUserSetting($widget_key, false);

    }

    $layouts[] = array($header);

    $dashboard = id(new AphrontMultiColumnView())
      ->setFluidlayout(true)
      ->setGutter(AphrontMultiColumnView::GUTTER_LARGE);

    $dashboard->addColumn(phutil_implode_html('', $layouts));

    return $dashboard;
  }
}
