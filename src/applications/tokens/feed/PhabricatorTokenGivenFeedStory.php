<?php

final class PhabricatorTokenGivenFeedStory
  extends PhabricatorFeedStory {

  public function getPrimaryObjectPHID() {
    return $this->getValue('objectPHID');
  }

  public function getRequiredHandlePHIDs() {
    $phids = array();
    $phids[] = $this->getValue('objectPHID');
    $phids[] = $this->getValue('authorPHID');
    return $phids;
  }

  public function getRequiredObjectPHIDs() {
    $phids = array();
    $phids[] = $this->getValue('tokenPHID');
    return $phids;
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

  private function renderTitle() {
    $token = $this->getObject($this->getValue('tokenPHID'));
    $object = $this->getObject($this->getValue('objectPHID'));
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
        '%s reacted %s a %s.',
        $this->linkTo($this->getValue('authorPHID')),
        $object_link,
        $token->getName());
    } else {
      $object_link = $this->linkTo($object->getPHID());
      $title = pht(
        '%s awarded %s a %s token.',
        $this->linkTo($this->getValue('authorPHID')),
        $object_link,
        $token->getName());
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

  public function renderAsTextForDoorkeeper(
    DoorkeeperFeedStoryPublisher $publisher) {
    // TODO: This is slightly wrong, as it does not respect implied context
    // on the publisher, so it will always say "awarded D123 a token" when it
    // should sometimes say "awarded this revision a token".
    return $this->renderText();
  }
}
