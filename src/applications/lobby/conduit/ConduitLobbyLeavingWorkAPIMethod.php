<?php

final class ConduitLobbyLeavingWorkAPIMethod extends
  ConduitLobbyAPIMethod {

    public function getAPIMethodName() {
    return 'lobby.leave.work';
  }

  public function getMethodDescription() {
    return pht('Leave Work');
  }

  public function getMethodSummary() {
    return pht('Leaving Work.');
  }

  protected function defineParamTypes() {
    return array();
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $user = $request->getViewer();
    $error = null;

    try {
      if (id(new Lobby())->setViewer($user)->allowedTojoin()) {
        id(new Lobby())
          ->setViewer($user)
          ->leavingWork(
        $user,
        PhabricatorContentSource::newForSource(
            SuiteContentSource::SOURCECONST));
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
