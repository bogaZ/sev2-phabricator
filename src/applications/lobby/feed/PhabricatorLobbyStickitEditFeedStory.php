<?php

final class PhabricatorLobbyStickitEditFeedStory
extends PhabricatorFeedStory {

  public function getPrimaryObjectPHID() {
    return $this->getValue('objectPHID');
  }

  public function renderView() {
    $view = $this->newStoryView();
    $author_phid = $this->getValue('authorPHID');
    $view->setAppIcon('fa-trophy');

    $href = $this->getHandle($this->getPrimaryObjectPHID())->getURI();
    $view->setHref($href);

    $view->setTitle($this->renderTitle());
    $view->setImage($this->getHandle($author_phid)->getImageURI());

    return $view;
  }

  private function getRoomPHID($phid) {
    $edge = id(new PhabricatorEdgeQuery())
      ->withEdgeTypes(
        array(
          LobbyStickitHasRoomEdgeType::EDGECONST,
        ))
      ->withSourcePHIDs(array($phid))
      ->execute();

    return array_keys($edge[$phid][LobbyStickitHasRoomEdgeType::EDGECONST])[0];
  }

  private function getRoom(string $phid) {
    return id(new ConpherenceThreadQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withPHIDs(array($phid))
      ->executeOne();
  }

  private function renderTitle() {
    $object_phid = $this->getValue('objectPHID');
    if (is_null($object_phid)) {
      phlog('is null '.$object_phid);
      return null;
    }
    $object = $this->getObject($object_phid);
    $type = $object->getNoteType();
    $room_phid = $this->getRoomPHID($object->getPHID());
    $room = $this->getRoom($room_phid);

    if ($object instanceof PhabricatorApplicationTransaction) {
      $trans_type = $object->hasComment() ?
        new PhutilSafeHTML('<b>"'.substr($object->getComment()->getContent()
            , 0, 30).'..."</b> at ')
        : 'activity at ';
      $trans_id = $object->getID();
      $trans_object_link = $object->renderHandleLink($object->getObjectPHID());
      $activity_link = preg_replace(
        '~href=("|\')(.+?)\1~',
        'href=$1$2#'.$trans_id.'$1',
        $trans_object_link->getHTMLContent());
      $object_link = new PhutilSafeHTML($trans_type.$activity_link);
      $title = pht(
        '%s reacted %s.',
        $this->linkTo($this->getValue('authorPHID')),
        $object_link);
    } else {
      $object_link = $this->linkTo($object->getPHID());
      $title = pht(
        '%s added %s %s in %s.',
        $this->linkTo($this->getValue('authorPHID')),
        $type,
        $object_link,
        $room->getTitle());
    }
    return $title;
  }

  public function renderText() {
    $old_target = $this->getRenderingTarget();
    $this->setRenderingTarget(PhabricatorApplicationTransaction::TARGET_TEXT);
    $title = $this->renderTitle();
    $this->setRenderingTarget($old_target);
    return $title;
  }
}
