<?php

final class ConduitLobbyGoalsSetAsReadAPIMethod extends
  ConduitLobbyAPIMethod {

    public function getAPIMethodName() {
    return 'lobby.goals.setasread';
  }

  public function getMethodDescription() {
    return pht('Set as read on goals');
  }

  public function getMethodSummary() {
    return pht('Set as read on goals.');
  }

  protected function defineParamTypes() {
    return array(
      'goalsPHID'   => 'required string',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $goals_phid = $request->getValue('goalsPHID');
    $user = $request->getUser();

    if (!$goals_phid) {
      return $this->setMessage('Goals PHID cannot be null', false);
    }

    $item = id(new LobbyStickitQuery())
              ->setViewer($request->getViewer())
              ->withPHIDs(array($goals_phid))
              ->needOwner(true)
              ->executeOne();
    if (!$item || $item->getNoteType() !== 'goals') {
      return $this->setMessage('Unable to get goals data', false);
    }

    $item->seenBy($user);

    return $this->setMessage('Success update goals seenby', true);

  }

  private function setMessage($message, $success) {
    return array(
      'message' => $message,
      'success' => $success,
    );
  }
}
