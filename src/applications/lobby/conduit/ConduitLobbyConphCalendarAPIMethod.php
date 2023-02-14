<?php

final class ConduitLobbyConphCalendarAPIMethod extends
  ConduitLobbyAPIMethod {

  public function getAPIMethodName() {
    return 'lobby.conph.calendar';
  }

  public function getMethodDescription() {
    return pht('Get Calendar in lobby conpherence');
  }

  public function getMethodSummary() {
    return pht('Get Calendar in lobby conpherence.');
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
      $calendars = id(new LobbyEdge())
        ->setViewer($user)
        ->setThread($conpherences)
        ->getCalendars();

      foreach ($calendars as $calendar) {
        $host = $calendar->getHost();

        $result['id'] = $calendar->getID();
        $result['phid'] = $calendar->getPHID();
        $result['name'] = $calendar->getName();
        $result['description'] = $calendar->getDescription();
        $result['host']['phid'] = $host->getPHID();
        $result['host']['username'] = $host->getUsername();
        $result['host']['fullname'] = $host->getFullName();
        $result['host']['profileImageURI'] = $host->getProfileImageURI();
        $result['icon'] = $calendar->getIcon();
        $result['isCancelled'] = (bool)$calendar->getIsCancelled();
        $result['isForDev'] = (bool)$calendar->getIsForDev();
        $result['isStub'] = (bool)$calendar->getIsStub();
        $result['isRecurring'] = (bool)$calendar->getIsRecurring();
        $result['parameters'] = $calendar->getParameters();

        // Get attender event list
        $invitees_query = id(new PhabricatorCalendarEventInviteeQuery())
          ->setViewer($user)
          ->withEventPHIDs(array($calendar->getPHID()))
          ->execute();

        $result['invitees'] = array();
        foreach ($invitees_query as $invitee) {
          $user = id(new PhabricatorPeopleQuery())
            ->setViewer($request->getViewer())
            ->needProfileImage(true)
            ->withPHIDs(array($invitee->getInviteePHID()))
            ->executeOne();
            if ($user) {
              $invitees['id'] = $user->getID();
              $invitees['username'] = $user->getUsername();
              $invitees['phid'] = $user->getPHID();
              $invitees['fullname'] = $user->getFullName();
              $invitees['profileImageURI'] = $user->getProfileImageURI();
              $invitees['status'] = $invitee->getStatus();
              $result['invitees'][] = $invitees;
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

    return $this->setMessage('Unable to get file data : '.$error, false);
  }

  private function setMessage($message, $success) {
    return array(
      'message' => $message,
      'success' => $success,
    );
  }
}
