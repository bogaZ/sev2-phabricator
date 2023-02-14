<?php

abstract class MoodConduitAPIMethod extends ConduitAPIMethod {

  protected function attachHandleToMood($mood, PhabricatorUser $user) {
    $handle = id(new PhabricatorHandleQuery())
      ->setViewer($user)
      ->withPHIDs(array($mood->getMoodPHID()))
      ->executeOne();
    $mood->attachHandle($handle);
  }

  protected function buildMoodInfoDictionary($mood) {
    return array(
      'id'            => $mood->getID(),
      'moodPHID'      => $mood->getMoodPHID(),
      'userPHID'      => $mood->getUserPHID(),
      'mood'          => $mood->getMood(),
      'description'   => $mood->getDescription(),
      'dateCreated'   => $mood->getDateCreated(),
      'dateModified'  => $mood->getDateModified(),
    );
  }

  protected function setResponseMessage($message, bool $error) {
    return array(
      'message' => $message,
      'error' => $error,
    );
  }
}
