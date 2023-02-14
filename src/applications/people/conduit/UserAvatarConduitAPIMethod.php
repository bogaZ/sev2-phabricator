<?php

final class UserAvatarConduitAPIMethod extends UserConduitAPIMethod {

  public function getAPIMethodName() {
    return 'user.avatar';
  }

  public function getMethodDescription() {
    return pht('Upload avatar for user');
  }

  protected function defineParamTypes() {
    return array(
      'data_base64' => 'required nonempty string (base64)',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $user = $request->getUser();
    $data = $request->getValue('data_base64');
    $params = array(
      'authorPHID' => $user->getPHID(),
      'canCDN' => true,
      'isExplicitUpload' => true,
    );

    if ($data === null || $data == '') {
      $user->setProfileImagePHID(null);
      $user->save();
      return array(
        'data' => 'Successfully delete profile picture',
        'error' => false
      );
    }
    
    $data = base64_decode($data, $strict = true);
    if ($data === false) {
      return array(
        'data' => null,
        'error' => 'Unable to decode base64 data!'
      );
    }

    $file = PhabricatorFile::newFromFileData($data, $params);
    $supported_formats = PhabricatorFile::getTransformableImageFormats();
    
    if (!$file->isTransformableImage()) {
      return array(
        'data' => null,
        'error' => pht('This server only supports these image formats: %s.',
        implode(', ', $supported_formats))
      );
    } else {
      $xform = PhabricatorFileTransform::getTransformByKey(
        PhabricatorFileThumbnailTransform::TRANSFORM_PROFILE);
      $xformed = $xform->executeTransform($file);
      $user->setProfileImagePHID($xformed->getPHID());
      $xformed->attachToObject($user->getPHID());
      $user->save();
      return array(
        'data' => 'Successfully upload profile picture',
        'error' => false
      );
    }
  }
}
