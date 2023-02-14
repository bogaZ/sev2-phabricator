<?php

final class ConduitLobbyStickitSetAsReadAPIMethod extends
  ConduitLobbyAPIMethod {

    public function getAPIMethodName() {
    return 'lobby.stickit.setasread';
  }

  public function getMethodDescription() {
    return pht('Set as read on stickit');
  }

  public function getMethodSummary() {
    return pht('Set as read on stickit.');
  }

  protected function defineParamTypes() {
    return array(
      'stickitPHID'   => 'required string',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $stickit_phid = $request->getValue('stickitPHID');
    $user = $request->getUser();

    if (!$stickit_phid) {
      return $this->setMessage('Stickit PHID cannot be null', false);
    }

    $item = id(new LobbyStickitQuery())
              ->setViewer($request->getViewer())
              ->withPHIDs(array($stickit_phid))
              ->needOwner(true)
              ->executeOne();

    if (!$item) {
      return $this->setMessage('Unable to get stickit data', false);
    }

    $item->seenBy($user);

    return $this->setMessage('Success update stickit seenby', true);

  }

  private function setMessage($message, $success) {
    return array(
      'message' => $message,
      'success' => $success,
    );
  }
}
