<?php

final class ConduitLobbyJoinLobbyAPIMethod extends
  ConduitLobbyAPIMethod {

    public function getAPIMethodName() {
    return 'lobby.join';
  }

  public function getMethodDescription() {
    return pht('Join Lobby');
  }

  public function getMethodSummary() {
    return pht('Join Lobby.');
  }

  protected function defineParamTypes() {
    return array(
      'device'        => 'required string | dekstop, phone',
      'reset_task'    => 'optional boolean',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $device = $request->getValue('device');
    $reset_task = $request->getValue('reset_task', false);

    if (!$device) {
        return $this->setMessage('Device not found', false);
    }

    $user = $request->getViewer();
    $error = null;

    try {
      if (id(new Lobby())->setViewer($user)->allowedTojoin()) {
        id(new Lobby())
          ->setViewer($user)
          ->joinLobby(
        $user,
        PhabricatorContentSource::newForSource(
          SuiteContentSource::SOURCECONST),
        $device,
        $reset_task);
        return $this->setMessage('Success Joined Lobby', true);
      }
      return $this->setMessage('User does not have permission', false);
    } catch (Exception $ex) {
      $error = $ex;
    } catch (Throwable $e) {
      $error = $e;
    }

    return $this->setMessage('Unable to join Lobby : '.$error, false);
  }

  private function setMessage($message, $success) {
    return array(
      'message' => $message,
      'success' => $success,
    );
  }
}
