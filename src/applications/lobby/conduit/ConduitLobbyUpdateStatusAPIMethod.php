<?php

final class ConduitLobbyUpdateStatusAPIMethod extends
  ConduitLobbyAPIMethod {

    public function getAPIMethodName() {
    return 'lobby.update.status';
  }

  public function getMethodDescription() {
    return pht('Update user status');
  }

  public function getMethodSummary() {
    return pht('Update user.');
  }

  protected function defineParamTypes() {
    $statuses = LobbyState::getStatusMap();
    $status = $this->formatStringConstants($statuses);
    return array(
      'status'        => 'required '.$status,
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $status = $request->getValue('status');

    $statuses = LobbyState::getStatusMap();

    if (!isset($statuses[$status])) {
      return $this->setMessage('Status not supported yet', false);
    }

    $user = $request->getViewer();
    $error = null;

    try {
      if (id(new Lobby())->setViewer($user)->allowedTojoin()) {
        id(new Lobby())
          ->setViewer($user)
          ->changeStatus(
        $user,
        PhabricatorContentSource::newForSource(
          SuiteContentSource::SOURCECONST),
        $status);
        return $this->setMessage('Status successfully updated', true);
      }
      return $this->setMessage('User does not have permission', false);
    } catch (Exception $ex) {
      $error = $ex;
    } catch (Throwable $e) {
      $error = $e;
    }

    return $this->setMessage('Unnable to update status : '.$error, false);
  }

  private function setMessage($message, $success) {
    return array(
      'message' => $message,
      'success' => $success,
    );
  }
}
