<?php

final class MentionEditConduitAPIMethod extends MentionConduitAPIMethod {

  public function getAPIMethodName() {
    return 'mention.edit';
  }

  public function getMethodDescription() {
    return pht('Create a Mention.');
  }

  protected function defineParamTypes() {
    return array(
      'message'     => 'required string',
      'objectPHID' => 'required phid',
      'authorPHID' => 'required phid',
    );
  }

  protected function defineReturnType() {
    return 'dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $object_phid = $request->getValue('objectPHID');
    $author_phid = $request->getValue('authorPHID');
    $message = $request->getValue('message');
    $viewer = $request->getViewer();

    if (!$author_phid) {
      $author_phid = $viewer->getPHID();
    }

    if (!$viewer->getPHID()) {
      return $this->setResponseMessage('User PHID cannot be null', true);
    }

    if (!$object_phid) {
      return $this->setResponseMessage('Object PHID cannot be null', true);
    }

    id(new PhabricatorMention())
      ->setCallerPHID($author_phid)
      ->setObjectPHID($object_phid)
      ->setMessage($message)
      ->save();

    return array(
      'message' => 'Successfully save mention data',
      'error' => false,
    );
  }
}
