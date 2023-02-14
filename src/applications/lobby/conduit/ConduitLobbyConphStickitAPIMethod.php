<?php

final class ConduitLobbyConphStickitAPIMethod extends
  ConduitLobbyAPIMethod {

    public function getAPIMethodName() {
    return 'lobby.conph.stickit';
  }

  public function getMethodDescription() {
    return pht('Get Stickit in lobby conpherence');
  }

  public function getMethodSummary() {
    return pht('Get Stickit in lobby conpherence.');
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
    $seen_profile = array();

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
        $stickits = id(new LobbyEdge())
          ->setViewer($user)
          ->setThread($conpherences)
          ->getStickits();

        foreach ($stickits as $stickit) {
          $owner = $stickit->getOwner();

          $result['id'] = $stickit->getID();
          $result['phid'] = $stickit->getPHID();
          $result['owner']['phid'] = $owner->getPHID();
          $result['owner']['username'] = $owner->getUsername();
          $result['owner']['fullname'] = $owner->getFullName();
          $result['owner']['profileImageURI'] = $owner->getProfileImageURI();
          $result['type'] = $stickit->getNoteType();
          $result['title'] = $stickit->getTitle();

          $engine = PhabricatorMarkupEngine::getEngine()
            ->setConfig('viewer', PhabricatorUser::getOmnipotentUser());

          $parsed_content = $engine->markupText($stickit->getContent());
          if ($parsed_content instanceof PhutilSafeHTML) {
            $parsed_content = $parsed_content->getHTMLContent();
          }

          $result['content'] = $stickit->getContent();
          $result['htmlContent'] = $parsed_content;
          $result['dateCreated'] = $stickit->getDateCreated();
          $result['seenCount'] = count($stickit->getSeenPHIDs());

          $users = id(new PhabricatorPeopleQuery())
            ->setViewer($user)
            ->needProfileImage(true)
            ->withPHIDs($stickit->getSeenPHIDs())
            ->execute();

          $result['seenProfile'] = array();
          foreach ($users as $user) {
            $seen_profile['id'] = $user->getID();
            $seen_profile['phid'] = $user->getPHID();
            $seen_profile['username'] = $user->getUsername();
            $seen_profile['fullname'] = $user->getFullName();
            $seen_profile['profileImageURI'] = $user->getProfileImageURI();
            $result['seenProfile'][] = $seen_profile;
          }

          $results[] = $result;
        }

        return array('data' => $results);

    } catch (Exception $ex) {
      $error = $ex;
    } catch (Throwable $e) {
      $error = $e;
    }

    return $this->setMessage('Unable to get stickit data : '.$error, false);
  }

  private function setMessage($message, $success) {
    return array(
      'message' => $message,
      'success' => $success,
    );
  }
}
