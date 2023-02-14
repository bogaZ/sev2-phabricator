<?php

final class ConduitLobbyWorkOnTaskAPIMethod extends
  ConduitLobbyAPIMethod {

    public function getAPIMethodName() {
    return 'lobby.work.on.task';
  }

  public function getMethodDescription() {
    return pht('Work on Task');
  }

  public function getMethodSummary() {
    return pht('Working on a task.');
  }

  protected function defineParamTypes() {
    return array(
      'task'   => 'optional string',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $task = $request->getValue('task');

    $user = $request->getViewer();
    $error = null;

    try {
      if (id(new Lobby())->setViewer($user)->allowedTojoin()) {
        id(new Lobby())
          ->setViewer($user)
          ->workOnTask(
        $user,
        PhabricatorContentSource::newForSource(
            SuiteContentSource::SOURCECONST),
        $task);
        return $this->setMessage('Success Updated Task', true);
      }
      return $this->setMessage('User does not have permission', false);
    } catch (Exception $ex) {
      $error = $ex;
    } catch (Throwable $e) {
      $error = $e;
    }

    return $this->setMessage('Unable to update task : '.$error, false);
  }

  private function setMessage($message, $success) {
    return array(
      'message' => $message,
      'success' => $success,
    );
  }
}
