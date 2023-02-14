<?php

final class ConduitLobbyConphTaskAPIMethod extends
  ConduitLobbyAPIMethod {

  public function getAPIMethodName() {
    return 'lobby.conph.task';
  }

  public function getMethodDescription() {
    return pht('Get Task in lobby conpherence');
  }

  public function getMethodSummary() {
    return pht('Get Task in lobby conpherence.');
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
      $tasks = id(new LobbyEdge())
        ->setViewer($user)
        ->setThread($conpherences)
        ->getTasks();

      foreach ($tasks as $task) {
        $status_value = $task->getStatus();
        $status_info = array(
          'value' => $status_value,
          'name' =>
            ManiphestTaskStatus::getTaskStatusName($status_value),
          'color' =>
            ManiphestTaskStatus::getStatusColor($status_value),
        );

        $priority_value = (int)$task->getPriority();
        $priority_info = array(
          'value' => $priority_value,
          'name' =>
            ManiphestTaskPriority::getTaskPriorityName($priority_value),
          'color' =>
            ManiphestTaskPriority::getTaskPriorityColor($priority_value),
        );

        $result['id'] = $task->getID();
        $result['phid'] = $task->getPHID();
        $result['ownerPHID'] = $task->getOwnerPHID();
        $result['authorPHID'] = $task->getAuthorPHID();
        $result['assigned'] = $task->getownerOrdering();
        $result['title'] = $task->getTitle();
        $result['description'] = $task->getDescription();

        $engine = PhabricatorMarkupEngine::getEngine()
          ->setConfig('viewer', PhabricatorUser::getOmnipotentUser());

        $parsed_description = $engine->markupText($task->getDescription());
        if ($parsed_description instanceof PhutilSafeHTML) {
          $parsed_description = $parsed_description->getHTMLContent();
        }

        $result['htmlDescription'] = $parsed_description;
        $result['status'] = $status_info;
        $result['dateCreated'] = (int)$task->getDateCreated();
        $result['dateModified'] = (int)$task->getDateModified();
        $result['priority'] = $priority_info;
        $result['points'] = $task->getPoints();
        $position_name = id(new ManiphestTask())
          ->getColumnPosition($task, $user);

        $result['position'] = $position_name;

        // get project detail for each maniphest
        $result['project'] = null;
        $edge_query = id(new PhabricatorEdgeQuery())
        ->withSourcePHIDs(array($task->getPHID()))
        ->withEdgeTypes(
          array(
            PhabricatorProjectObjectHasProjectEdgeType::EDGECONST,
          ));

        $edge_query->execute();
        if ($edge_query) {
          $project_phids = $edge_query->getDestinationPHIDs(array($task->getPHID()));
          if ($project_phids) {
            $projects = id(new PhabricatorProjectQuery())
              ->setViewer($user)
              ->needImages(true)
              ->withPHIDs($project_phids)
              ->execute();
            $maniphest_project = array();
            if ($projects) {
              foreach ($projects as $project) {
                $maniphest_project['id'] = $project->getID();
                $maniphest_project['phid'] = $project->getPHID();
                $maniphest_project['status'] = $project->getStatus();
                $maniphest_project['name'] = $project->getName();
                $maniphest_project['profileImageURI'] = $project->getProfileImageURI();
                $result['project'] = $maniphest_project;
              }
            }
          }
        }

        $subscribers = id(new ManiphestTask())
          ->getSubscribers($task, $user);

        $result['subscribers'] = $subscribers;

        $results[] = $result;
      }

      return array('data' => $results);

    } catch (Exception $ex) {
      $error = $ex;
    } catch (Throwable $e) {
      $error = $e;
    }

    return $this->setMessage('Unable to get task data : '.$error, false);
  }

  private function setMessage($message, $success) {
    return array(
      'message' => $message,
      'success' => $success,
    );
  }
}
