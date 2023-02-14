<?php

final class ConduitLobbyJoinChannelAPIMethod extends
  ConduitLobbyAPIMethod {

    public function getAPIMethodName() {
    return 'lobby.join.channel';
  }

  public function getMethodDescription() {
    return pht('Join Channel from Lobby');
  }

  public function getMethodSummary() {
    return pht('Join Lobby Channel from Lobby.');
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

    if (!$channel_phid) {
        return $this->setMessage('ChannelPHID cannot be null', false);
    }

    $user = $request->getViewer();
    $error = null;

    try {
      $conpherence_participants = id(new ConpherenceParticipantQuery())
          ->withConpherencePHIDs(array($channel_phid))
          ->execute();
      $participant_phids = mpull($conpherence_participants, 'getParticipantPHID');

      if (id(new Lobby())->setViewer($user)->allowedTojoin()
        && in_array($user->getPHID(), $participant_phids)) {
        id(new Lobby())
          ->setViewer($user)
          ->joinChannel(
        $user,
        PhabricatorContentSource::newForSource(
            SuiteContentSource::SOURCECONST),
        $channel_phid);
        return $this->setMessage('Success Joined Channel', true);
      }
      return $this->setMessage('User does not have permission', false);
    } catch (Exception $ex) {
      $error = $ex;
    } catch (Throwable $e) {
      $error = $e;
    }

    return $this->setMessage('Unable to join Channel : '.$error, false);
  }

  private function setMessage($message, $success) {
    return array(
      'message' => $message,
      'success' => $success,
    );
  }
}
