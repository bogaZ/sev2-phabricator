<?php

final class MoodEditConduitAPIMethod extends MoodConduitAPIMethod {

  public function getAPIMethodName() {
    return 'mood.edit';
  }

  public function getMethodDescription() {
    return pht('Create a mood.');
  }

  protected function defineParamTypes() {
    return array(
      'mood'        => 'required string',
      'message'     => 'optional string',
      'isForDev'    => 'optional boolean (default 0)',
    );
  }

  protected function defineReturnType() {
    return 'dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $mood = $request->getValue('mood');
    $message = $request->getValue('message');
    $is_for_dev = (bool)$request->getValue('isForDev');
    $viewer = $request->getViewer();

    if (!$viewer->getPHID()) {
      return $this->setResponseMessage('User PHID cannot be null', true);
    }

    if (!$mood) {
      return $this->setResponseMessage('Mood cannot be null', true);
    }

    $user_found = id(new PhabricatorPeopleQuery())
    ->setViewer($viewer)
    ->withPHIDs(array($viewer->getPHID()))
    ->executeOne();

    if (!$user_found) {
      return $this->setResponseMessage('User not found', true);
    }

    id(new PhabricatorMood())
    ->setUserPHID($viewer->getPHID())
    ->setMood($mood)
    ->setMessage($message)
    ->setIsForDev($is_for_dev)
    ->save();

    return array(
      'message' => 'Successfully save mood data',
      'error' => false,
    );
  }

}
