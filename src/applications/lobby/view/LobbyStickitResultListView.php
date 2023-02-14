<?php

final class LobbyStickitResultListView extends AphrontView {

  private $baseUri;
  private $noDataString;
  private $items;
  private $user;

  public function setBaseUri($base_uri) {
    $this->baseUri = $base_uri;
    return $this;
  }

  public function setUser(PhabricatorUser $user) {
    $this->user = $user;
    return $this;
  }

  protected function getUser() {
    return $this->user;
  }

  public function setItems(array $items) {
    $this->items = $items;
    return $this;
  }

  public function getItems() {
    return $this->items;
  }

  public function render() {
    $viewer = $this->getUser();
    $items = $this->getItems();
    assert_instances_of($items, 'LobbyStickit');
    $handles = id(new PhabricatorHandleQuery())
          ->setViewer(PhabricatorUser::getOmnipotentUser())
          ->withPHIDs(mpull($items,'getOwnerPHID'))
          ->execute();

    $lists = array();

    foreach(LobbyStickit::getTypeMap() as $type => $label) {
      $group = array();
      foreach ($items as $s) {
        if ($type == $s->getNoteType()) {
          $item = id(new PHUIObjectItemView())
            ->setUser($viewer)
            ->setObject($s)
            ->setHeader($s->getTitle())
            ->setHref($s->getViewURI());

          if ($s->getOwnerPHID()) {
            $owner = $handles[$s->getOwnerPHID()];
            $item->addByline(pht('By: %s', $owner->renderLink()));
          }

          $item->addIcon(
            'none',
            phabricator_datetime($s->getDateModified(), $viewer));

          $item->setStatusIcon('fa-check '.$s->getNoteTypeColor());
          $item->addAttribute(id(new PHUITagView())
                  ->setType(PHUITagView::TYPE_SHADE)
                  ->setColor(PHUITagView::COLOR_BLUE)
                  ->setIcon('fa-eye')
                  ->setSlimShady(true)
                  ->setName(pht(' %d people(s)', count($s->getSeenPHIDs()))));

          $group[] = $item;
        }
      }

      if (!empty($group)) {
        $list = new PHUIObjectItemListView();
        foreach($group as $member) {
          $list->addItem($member);
        }

        $header = id(new PHUIHeaderView())
          ->addSigil('task-group')
          ->setHeader(pht('%s (%s)', $label, phutil_count($group)));

        $lists[] = id(new PHUIObjectBoxView())
          ->setHeader($header)
          ->setObjectList($list);
      }
    }

    return $lists;
  }

}
