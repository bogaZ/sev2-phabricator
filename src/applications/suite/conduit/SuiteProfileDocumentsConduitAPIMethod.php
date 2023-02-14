<?php

final class SuiteProfileDocumentsConduitAPIMethod
  extends SuiteConduitAPIMethod {

  public function getAPIMethodName() {
    return 'suite.profile.documents';
  }

  public function getMethodStatus() {
    return self::METHOD_STATUS_FROZEN;
  }

  public function getMethodDescription() {
    return pht('Upload user documents.');
  }

  protected function defineParamTypes() {
    return array(
      'data_base64' => 'required non empty base64-bytes',
      'docType' => 'required doc type',
      'filename' => 'required filename',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $this->getViewer();

    $user = $request->getUser();

    if (!$user) {
      throw new ConduitException('ERR_USER_NOT_FOUND');
    }

    $this->enforceSuiteOnly($user);

    $profile = SuiteProfileQuery::loadProfileForUser($user,
      $request->newContentSource());

    // Get Doc type
    $doc_type = $request->getValue('docType');
    if (!array_key_exists($doc_type, SuiteProfile::getDocsMap())) {
      throw new ConduitException('ERR_DOCTYPE_NOT_FOUND');
    }

    $filename = $request->getValue('filename');

    $data = $request->getValue('data_base64');
    $data = $this->decodeBase64($data);

    $params = array(
      'authorPHID' => $viewer->getPHID(),
      'canCDN' => true,
      'isExplicitUpload' => true,
    );

    if ($filename !== null) {
      $params['name'] = $filename;
    }

    $file = PhabricatorFile::newFromFileData($data, $params);
    $file_phid = $file->getPHID();

    if (($doc_type == SuiteProfile::DOC_IDENTITY)
      || ($doc_type == SuiteProfile::DOC_TAX)) {
      // Transform as ID card type
      $xform = PhabricatorFileTransform::getTransformByKey(
        PhabricatorFileThumbnailTransform::TRANSFORM_ID_DOC);
    } else {
      // Transform as other doc type
      $xform = PhabricatorFileTransform::getTransformByKey(
        PhabricatorFileThumbnailTransform::TRANSFORM_OTHER_DOC);
    }

    $xformed = $xform->executeTransform($file);

    switch ($doc_type) {
      case SuiteProfile::DOC_TAX:
        $profile->setTaxDocPHID($xformed->getPHID());
        break;

      case SuiteProfile::DOC_FAMILY:
        $profile->setFamilyDocPHID($xformed->getPHID());
        break;

      case SuiteProfile::DOC_SKCK:
        $profile->setSkckDocPHID($xformed->getPHID());
        break;

      case SuiteProfile::DOC_DOMICILE:
        $profile->setDomicileDocPHID($xformed->getPHID());
        break;

      case SuiteProfile::DOC_CERTIFICATE:
        $profile->setCertificateDocPHID($xformed->getPHID());
        break;

      case SuiteProfile::DOC_OTHER:
        $profile->setOtherDocPHID($xformed->getPHID());
        break;

      case SuiteProfile::DOC_ADDITIONAL:
        $profile->setAdditionalDocPHID($xformed->getPHID());
        break;

      default:
        $profile->setIdentityDocPHID($xformed->getPHID());
        break;
    }

    $xformed->attachToObject($profile->getPHID());
    $profile->save();

    return array(
      $doc_type => $file->getPHID(),
    );
  }

  protected function decodeBase64($data) {
    $data = base64_decode($data, $strict = true);
    if ($data === false) {
      throw new Exception(pht('Unable to decode base64 data!'));
    }
    return $data;
  }

}
