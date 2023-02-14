<?php

final class MoodUserSearchEngineAttachment
  extends PhabricatorSearchEngineAttachment {

  public function getAttachmentName() {
    return pht('User Mood');
  }

  public function getAttachmentDescription() {
    return pht('Get the user for the mood.');
  }

  public function loadAttachmentData(array $objects, $spec) {
    return $this->getAttachmentKey();
  }

  public function getAttachmentForObject($object, $data, $spec) {
    if ($data == 'user') {
      if ($spec) {
        $users_mood = id(new PhabricatorPeopleQuery())
              ->setViewer(PhabricatorUser::getOmnipotentUser())
              ->withPHIDs(array($object->getUserPHID()))
              ->needProfile(true)
              ->needProfileImage(true)
              ->execute();
        $user_mood = head($users_mood);
        return array(
          'username' => $user_mood->getUsername(),
          'fullname' => $user_mood->getFullName(),
          'profileImageURI' => $user_mood->getProfileImageURI(),
        );
      }
    }
    return (object)array();
  }

}
