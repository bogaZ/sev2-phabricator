<?php

final class LobbyStateController
  extends LobbyController {

  public function shouldAllowPublic() {
    return true;
  }

  protected function afterMetRequiredCapabilities(AphrontRequest $request) {

    if (!$request->isAjax()) {
      $user = $request->getUser();
      $content_source = PhabricatorContentSource::newFromRequest($request);

      $joined = false;
      $lobby = id(new LobbyStateQuery())
                ->setViewer($user)
                ->withOwnerPHIDs(array($user->getPHID()))
                ->executeOne();

      if (!$lobby) {
        return new Aphront404Response();
      }

      try {
        $task_phids = PhabricatorEdgeQuery::loadDestinationPHIDs(
          $lobby->getPHID(),
          LobbyHasTaskEdgeType::EDGECONST);

        $tasks = id(new ManiphestTaskQuery())
                ->setViewer($user)
                ->withPHIDs($task_phids)
                ->execute();

        $task = head($tasks);

        if ($task) {
          $lobby = id(new Lobby())
                    ->setViewer($user)
                    ->workOnTask(
            $user, $content_source,
            $task->getMonogram());
        }

        LobbyAphlict::broadcastLobby();
      } catch (Exception $ex) {
        $error = $ex;
      } catch (Throwable $e) {
        $error = $e;
      }
    }

    return id(new AphrontRedirectResponse())->setURI('/');
  }

  protected function requiresManageCapability() {
    return false;
  }

  protected function requiresJoinCapability() {
    return true;
  }
}
