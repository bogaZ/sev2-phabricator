<?php

final class LobbyPublicThreadController
  extends PhabricatorController {

  protected $conpherence;

  public function shouldAllowPublic() {
    return true;
  }

  public function shouldRequireLogin() {
    return false;
  }

  public function handleRequest(AphrontRequest $request) {
    $user = id(new PhabricatorPeopleQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withUsernames(array('sev2bot'))
      ->executeOne();

    $title = pht('Conpherence');
    $conpherence = null;

    $limit = ConpherenceThreadListView::SEE_ALL_LIMIT + 1;
    $all_participation = array();

    $conpherence_id = $request->getURIData('id');
    $conpherence = id(new ConpherenceThreadQuery())
      ->setViewer($user)
      ->withIDs(array($conpherence_id))
      ->executeOne();
    if (!$conpherence) {
      return new Aphront404Response();
    }
    if ($conpherence->getTitle()) {
      $title = $conpherence->getTitle();
    }
    $cursor = $conpherence->getParticipantIfExists($user->getPHID());
    $data = $this->loadDefaultParticipation($limit);
    $all_participation = $data['all_participation'];
    if (!$cursor) {
      $menu_participation = id(new ConpherenceParticipant())
        ->makeEphemeral()
        ->setConpherencePHID($conpherence->getPHID())
        ->setParticipantPHID($user->getPHID());
    } else {
      $menu_participation = $cursor;
    }

    // check to see if the loaded conpherence is going to show up
    // within the SEE_ALL_LIMIT amount of conpherences.
    // If its not there, then we just pre-pend it as the "first"
    // conpherence so folks have a navigation item in the menu.
    $count = 0;
    $found = false;
    foreach ($all_participation as $phid => $curr_participation) {
      if ($conpherence->getPHID() == $phid) {
        $found = true;
        break;
      }
      $count++;
      if ($count > ConpherenceThreadListView::SEE_ALL_LIMIT) {
        break;
      }
    }
    if (!$found) {
      $all_participation =
        array($conpherence->getPHID() => $menu_participation) +
        $all_participation;
    }

    $threads = $this->loadConpherenceThreadData($all_participation);

    $thread_view = id(new ConpherenceThreadListView())
      ->setUser($user)
      ->setBaseURI($this->getApplicationURI())
      ->setThreads($threads);

    return id(new AphrontAjaxResponse())->setContent($thread_view);
  }

  private function loadDefaultParticipation($limit) {
    $viewer = $this->getRequest()->getUser();

    $all_participation = id(new ConpherenceParticipantQuery())
      ->withParticipantPHIDs(array($viewer->getPHID()))
      ->setLimit($limit)
      ->execute();
    $all_participation = mpull($all_participation, null, 'getConpherencePHID');

    return array(
      'all_participation' => $all_participation,
    );
  }

  private function loadConpherenceThreadData($participation) {
    $user = $this->getRequest()->getUser();
    $conpherence_phids = array_keys($participation);
    $conpherences = array();
    if ($conpherence_phids) {
      $conpherences = id(new ConpherenceThreadQuery())
        ->setViewer($user)
        ->withPHIDs($conpherence_phids)
        ->needProfileImage(true)
        ->execute();

      // this will re-sort by participation data
      $conpherences = array_select_keys($conpherences, $conpherence_phids);
    }

    return $conpherences;
  }
}
