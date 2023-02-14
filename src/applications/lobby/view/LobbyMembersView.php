<?php

final class LobbyMembersView extends AphrontView {

  private $threadPHID;
  private $states = array();

  public function setThreadPHID($phid) {
    $this->threadPHID = $phid;
    return $this;
  }

  public function getThreadPHID() {
    return $this->threadPHID;
  }

  public function setStates($states) {
    $this->states = $states;
    return $this;
  }

  public function getStates() {
    return $this->states;
  }

  public function render() {
    $states = $this->getStates();

    $max_count = 8;
    $badges = array();
    $left_over_text = array();
    $left_over_badge = id(new PHUIBadgeMiniView())
      ->addClass('lobby-members-last-item')
      ->setQuality(PhabricatorBadgesQuality::EPIC);

    $current_count = 0;
    foreach($states as $state) {
      $current_count++;

      $owner = $state->getOwner();

      if ($current_count > $max_count) {
        $left_over_text[] = pht('%s', $owner->getRealName());
      } else {
        $current_task = $state->getCurrentTask()
                      ? $state->getCurrentTask()
                      : 'Just mingling';

        $badges[] = id(new PHUIBadgeMiniView())
            ->setImage($owner->getProfileImageURI())
            ->setHeader(pht('%s : %s',
              $owner->getUserName(),
              $current_task));
      }
    }

    // Set left-over
    if (!empty($left_over_text)) {
      $count = phutil_tag('span', array(
        'class' => 'lobby-members-leftover'
      ), pht('+%d', count($left_over_text)));
      $badges[] = $left_over_badge->appendChild($count)
                            ->setHeader(implode(',', $left_over_text));
    }

    $flex = new PHUIBadgeBoxView();
    $flex->addItems($badges);
    $flex->setCollapsed(true);
    $flex->addClass('ml');

    return $flex;
  }

}
