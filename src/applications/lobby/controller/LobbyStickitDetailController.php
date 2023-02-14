<?php

final class LobbyStickitDetailController
  extends LobbyController {

  private $item;

  public function setItem(LobbyStickit $item) {
    $this->item = $item;
    return $this;
  }

  public function getItem() {
    return $this->item;
  }

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {
    $viewer = $request->getViewer();
    $id = $request->getURIData('id');

    $items = id(new LobbyStickitQuery())
      ->setViewer($viewer)
      ->withIDs(array($id))
      ->execute();
    $item = head($items);
    if (!$item) {
      return new Aphront404Response();
    }

    $this->setItem($item);

    $crumbs = $this->buildApplicationCrumbs();
    $title = $item->getTitle();

    $options = LobbyStickit::getTypeMap();
    $selected_type = $item->getNoteType();
    $header = id(new PHUIHeaderView())
      ->setHeader($item->getTitle())
      ->setUser($viewer)
      ->addTag(id(new PHUITagView())
        ->setType(PHUITagView::TYPE_SHADE)
        ->setColor($item->getNoteTypeColor())
        ->setIcon('fa-check')
        ->setName($options[$selected_type]))
      ->setPolicyObject($item);

    $curtain = $this->buildCurtain($item);
    $content = $this->buildContentView($item);

    $timeline = $this->buildTransactionTimeline(
      $item,
      new LobbyStickitTransactionQuery());

    $comment_view = id(new LobbyStickitEditEngine())
      ->setViewer($viewer)
      ->buildEditEngineCommentView($item);

    $view = id(new PHUITwoColumnView())
      ->setHeader($header)
      ->setCurtain($curtain)
      ->setMainColumn(array(
          $timeline,
          $comment_view,
        ))
      ->addPropertySection(pht('written by %s', $item->loadUser()->getRealName()), $content);

    $item->seenBy($viewer);

    return $this->newPage()
      ->setTitle($title)
      ->setCrumbs($crumbs)
      ->setPageObjectPHIDs(array($item->getPHID()))
      ->appendChild($view);
  }

  protected function buildApplicationCrumbs() {
    $item = $this->getItem();
    $id = $item->getID();
    $paths_uri = $this->getApplicationURI("/");
    $item_uri = $this->getApplicationURI("/stickit/{$id}/");

    $crumbs = parent::buildApplicationCrumbs();
    $crumbs->addTextCrumb("Stick-It", $paths_uri);
    $crumbs->addTextCrumb($item->getTitle(), $item_uri);
    $crumbs->setBorder(true);
    return $crumbs;
  }

  protected function requiresManageCapability() {
    return false;
  }

  protected function requiresJoinCapability() {
    return true;
  }

  private function buildCurtain(LobbyStickit $item) {
    $viewer = $this->getViewer();

    $can_edit = PhabricatorPolicyFilter::hasCapability(
      $viewer,
      $item,
      PhabricatorPolicyCapability::CAN_EDIT);

    $id = $item->getID();
    $edit_uri = $this->getApplicationURI("/stickit/edit/{$id}/");

    $curtain = $this->newCurtainView($item);

    $curtain->addAction(
      id(new PhabricatorActionView())
        ->setName(pht('Edit'))
        ->setIcon('fa-pencil')
        ->setDisabled(!$can_edit)
        ->setHref($edit_uri));

    // Build seen people
    $curtain->addPanel($this->buildSeenPanel());

    return $curtain;
  }

  private function buildContentView(
    LobbyStickit $item) {
    $viewer = $this->getViewer();

    $view = id(new PHUIPropertyListView())
      ->setUser($viewer);

    $content = $item->getContent();
    if (strlen($content)) {
      $view->addTextContent(
        new PHUIRemarkupView($viewer, $content));
    }

    return $view;
  }

  private function buildSeenPanel() {
    $me = $this->getRequest()->getViewer();
    $panel = id(new PHUICurtainPanelView())
              ->setHeaderText('Seen By');

    $viewers_phids = $this->getItem()->getSeenPHIDs();

    $users = id(new PhabricatorPeopleQuery())
                    ->setViewer(PhabricatorUser::getOmnipotentUser())
                    ->withPHIDs($viewers_phids)
                    ->needProfile(true)
                    ->needProfileImage(true)
                    ->execute();

    $users = mpull($users, null, 'getPHID');

    if (!array_key_exists($me->getPHID(), $users)) {
      $my_phid = $me->getPHID();
      $users[$my_phid] = $me;
    }

    $viewers = array();
    $flex = new PHUIBadgeBoxView();

    foreach($users as $user) {
      $viewers[] = id(new PHUIBadgeMiniView())
          ->setImage($user->getProfileImageURI())
          ->setHeader(pht('%s', $user->getUserName()));
    }
    $flex->addItems($viewers);

    return $panel->appendChild($flex);
  }
}
