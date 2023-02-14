<?php

final class ConduitLobbyConphFileAPIMethod extends
  ConduitLobbyAPIMethod {

    public function getAPIMethodName() {
    return 'lobby.conph.file';
  }

  public function getMethodDescription() {
    return pht('Get File in lobby conpherence');
  }

  public function getMethodSummary() {
    return pht('Get File in lobby conpherence.');
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
        $files = id(new LobbyEdge())
          ->setViewer($user)
          ->setThread($conpherences)
          ->getFiles();

        foreach ($files as $file) {
          $author = $file->getAuthor();
          $result['id'] = $file->getId();
          $result['phid'] = $file->getPHID();
          $result['name'] = $file->getName();
          $result['uri'] = PhabricatorEnv::getURI($file->getURI());
          $result['dataURI'] = $file->getCDNUri('data');
          $result['size'] = (int)$file->getByteSize();
          $result['mimeType'] = $file->getMimeType();
          $result['alt'] = array(
            'custom' => $file->getCustomAltText(),
            'default' => $file->getDefaultAltText(),
          );
          $result['author'] = array(
              'phid' => $author->getPHID(),
              'username' => $author->getUsername(),
              'fullname' => $author->getFullName(),
              'profileImageURI' => $author->getProfileImageURI(),
          );
          $result['createdAt'] = $file->getDateCreated();
          $result['updatedAt'] = $file->getDateModified();

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
