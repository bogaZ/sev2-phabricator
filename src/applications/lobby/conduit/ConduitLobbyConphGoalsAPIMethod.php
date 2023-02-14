<?php

final class ConduitLobbyConphGoalsAPIMethod extends
  ConduitLobbyAPIMethod {

    public function getAPIMethodName() {
    return 'lobby.conph.goals';
  }

  public function getMethodDescription() {
    return pht('Get Goals in lobby conpherence');
  }

  public function getMethodSummary() {
    return pht('Get Goals in lobby conpherence.');
  }

  protected function defineParamTypes() {
    return array(
      'channelPHID'   => 'required string',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $channel_phid = $request->getValue('channelPHID');
    $result = array();
    $results = array();
    $seen_profile = array();

    if (!$channel_phid) {
        return $this->setMessage('ChannelPHID cannot be null', false);
    }

    $user = $request->getViewer();

    $conpherences = id(new ConpherenceThreadQuery())
        ->setViewer($user)
        ->withPHIDs(array($channel_phid))
        ->needProfileImage(true)
        ->executeOne();
    try {
        $goals = id(new LobbyEdge())
          ->setViewer($user)
          ->setThread($conpherences)
          ->getGoals();
        foreach ($goals as $goal) {
          $owner = $goal->getOwner();

          $result['id'] = $goal->getID();
          $result['phid'] = $goal->getPHID();
          $result['owner']['phid'] = $owner->getPHID();
          $result['owner']['username'] = $owner->getUsername();
          $result['owner']['fullname'] = $owner->getFullName();
          $result['owner']['profileImageURI'] = $owner->getProfileImageURI();
          $result['type'] = $goal->getNoteType();
          $result['title'] = $goal->getTitle();

          $engine = PhabricatorMarkupEngine::getEngine()
            ->setConfig('viewer', PhabricatorUser::getOmnipotentUser());

          $parsed_content = $engine->markupText($goal->getContent());
          if ($parsed_content instanceof PhutilSafeHTML) {
            $parsed_content = $parsed_content->getHTMLContent();
          }

          $result['content'] = $goal->getContent();
          $result['htmlContent'] = $parsed_content;
          $result['dateCreated'] = $goal->getDateCreated();
          $result['seenCount'] = count($goal->getSeenPHIDs());

          $users = id(new PhabricatorPeopleQuery())
            ->setViewer($user)
            ->needProfileImage(true)
            ->withPHIDs($goal->getSeenPHIDs())
            ->execute();

          $result['seenProfile'] = array();
          foreach ($users as $user) {
            $seen_profile['id'] = $user->getID();
            $seen_profile['phid'] = $user->getPHID();
            $seen_profile['username'] = $user->getUsername();
            $seen_profile['fullname'] = $user->getFullName();
            $seen_profile['profileImageURI'] = $user->getProfileImageURI();
            $result['seenProfile'][] = $seen_profile;
          }

          $task_phids = PhabricatorEdgeQuery::loadDestinationPHIDs(
            $goal->getPHID(),
            LobbyGoalsHasManiphestEdgeType::EDGECONST);
          if (empty($task_phids)) {
            $result['maniphest'][] = array();
          } else {
            $maniphest_task = array();
            $tasks = id(new ManiphestTaskQuery())
                    ->setViewer(PhabricatorUser::getOmnipotentUser())
                    ->withPHIDs($task_phids)
                    ->execute();
            foreach ($tasks as $task) {
              $owner_name = id(new PhabricatorPeopleQuery())
              ->setViewer($task->getViewer())
              ->withPHIDs(array($task->getOwnerPHID()))
              ->executeOne();

              if ($owner_name) {
                $owner_name = $owner_name->getUserName();
              }

              $owner_name_qa = id(new PhabricatorPeopleQuery())
              ->setViewer($task->getViewer())
              ->withPHIDs(array($task->getOwnerQAPHID()))
              ->executeOne();

              if ($owner_name_qa) {
                $owner_name_qa = $owner_name_qa->getUserName();
              }
              $status_value = $task->getStatus();
              $status_info = array(
                'value' => $status_value,
                'name' => ManiphestTaskStatus::getTaskStatusName($status_value),
                'color' => ManiphestTaskStatus::getStatusColor($status_value),
              );
              $maniphest_task['id'] = $task->getID();
              $maniphest_task['phid'] = $task->getPHID();
              $maniphest_task['title'] = $task->getTitle();
              $maniphest_task['assigned'] = $owner_name;
              $maniphest_task['tester'] = $owner_name_qa;
              $maniphest_task['points'] = $task->getPoints();
              $maniphest_task['pointsQA'] = $task->getPointsQA();
              $maniphest_task['status'] =  $status_info;
              $result['maniphest'][] = $maniphest_task;
            }
          }
          $results[] = $result;
        }

        return array('data' => $results);

    } catch (Exception $ex) {
      $error = $ex;
    } catch (Throwable $e) {
      $error = $e;
    }

    return $this->setMessage('Unable to get goals data : '.$error, false);
  }

  private function setMessage($message, $success) {
    return array(
      'message' => $message,
      'success' => $success,
    );
  }
}
