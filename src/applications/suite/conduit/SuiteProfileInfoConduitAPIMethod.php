<?php

final class SuiteProfileInfoConduitAPIMethod extends SuiteConduitAPIMethod {

  public function getAPIMethodName() {
    return 'suite.profile.info';
  }

  public function getMethodStatus() {
    return self::METHOD_STATUS_FROZEN;
  }

  public function getMethodDescription() {
    return pht('Retrieve primary suite profile.');
  }

  protected function defineParamTypes() {
    return array(
      'userPHID' => 'required user phid',
    );
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $this->getViewer();

    $user_phid = $request->getValue('userPHID');
    $user = id(new PhabricatorPeopleQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withPHIDs(array($user_phid))
      ->executeOne();

    if (!$user) {
      throw new ConduitException('ERR_USER_NOT_FOUND');
    }

    $this->enforceSuiteOnly($user);

    $profile = SuiteProfileQuery::loadProfileForUser($user,
      $request->newContentSource());

    // Get onboard status
    $selected_coursepath = null;
    $all_enrollments = id(new CoursepathItemEnrollmentQuery())
                      ->setViewer(PhabricatorUser::getOmnipotentUser())
                      ->withRegistrarPHIDs(array($user_phid))
                      ->execute();

    $coursepath_phids = mpull(
      $all_enrollments, 'getItemPHID');

    $has_coursepath = head($all_enrollments);

    $is_finished_onboarding = $has_coursepath
      && $profile->getUpFor() != 'undefined';

    $coursepath_results = array();
    $coursepath_result = array();
    if ($has_coursepath) {
      $selected_coursepath = id(new CoursepathItemQuery())
                      ->setViewer(PhabricatorUser::getOmnipotentUser())
                      ->withPHIDs(array_values($coursepath_phids))
                      ->execute();

      foreach ($selected_coursepath as $coursepath) {
        $coursepath_result['phid'] = $coursepath->getPHID();
        $coursepath_result['name'] = $coursepath->getName();
        $coursepath_results[] = $coursepath_result;
      }
    }

    $identity_docs = array();
    $tax_docs = array();
    $family_docs = array();
    $sksck_docs = array();
    $domicile_docs = array();
    $certificate_docs = array();
    $other_docs = array();
    $additional_docs = array();

    $uploaded_docs = array();

    if ($uploaded = $profile->getIdentityDocPHID()) {
      $identity_docs['imageUrl'] = $this->getFile(
          $viewer,
          $profile->getIdentityDocPHID());
      $identity_docs['name'] = 'identityDoc';

      $uploaded_docs[] = $identity_docs;
    }

    if ($uploaded = $profile->getTaxDocPHID()) {
      $tax_docs['imageUrl'] = $this->getFile(
        $viewer,
        $profile->getTaxDocPHID());
      $tax_docs['name'] = 'taxDoc';

      $uploaded_docs[] = $tax_docs;
    }

    if ($uploaded = $profile->getFamilyDocPHID()) {
      $family_docs['imageUrl'] = $this->getFile(
        $viewer,
        $profile->getFamilyDocPHID());
      $family_docs['name'] = 'familyDoc';

      $uploaded_docs[] = $family_docs;
    }

    if ($uploaded = $profile->getSkckDocPHID()) {
      $sksck_docs['imageUrl'] = $this->getFile(
        $viewer,
        $profile->getSkckDocPHID());
      $sksck_docs['name'] = 'skckDoc';

      $uploaded_docs[] = $sksck_docs;
    }

    if ($uploaded = $profile->getDomicileDocPHID()) {
      $domicile_docs['imageUrl'] = $this->getFile(
        $viewer,
        $profile->getDomicileDocPHID());
      $domicile_docs['name'] = 'domicileDoc';

      $uploaded_docs[] = $domicile_docs;
    }

    if ($uploaded = $profile->getCertificateDocPHID()) {
      $certificate_docs['imageUrl'] = $this->getFile(
        $viewer,
        $profile->getCertificateDocPHID());
      $certificate_docs['name'] = 'certificateDoc';

      $uploaded_docs[] = $certificate_docs;
    }

    if ($uploaded = $profile->getOtherDocPHID()) {
      $other_docs['imageUrl'] = $this->getFile(
        $viewer,
        $profile->getOtherDocPHID());
      $other_docs['name'] = 'otherDoc';

      $uploaded_docs[] = $other_docs;
    }

    if ($uploaded = $profile->getAdditionalDocPHID()) {
      $additional_docs['imageUrl'] = $this->getFile(
        $viewer,
        $profile->getAdditionalDocPHID());
      $additional_docs['name'] = 'additionalDoc';

      $uploaded_docs[] = $additional_docs;
    }

    $result = array(
      'id'                     => $profile->getID(),
      'phid'                   => $profile->getPHID(),
      'realName'               => $user->getRealName(),
      'userPHID'               => $profile->getOwnerPHID(),
      'isOnboard'              => $is_finished_onboarding,
      'isSuite'                => $user->getIsSuite(),
      'isSuiteDisabled'        => $user->getIsSuiteDisabled(),
      'isSuiteSubscribed'      => $user->getIsSuiteSubscribed(),
      'isRsp'                  => $profile->getIsRsp(),
      'isEligibleForJob'       => $profile->getIsEligibleForJob(),
      'graduationTargetMonth'  => (int)$profile->getGraduationTargetMonth(),
      'uploadedDocs'           => $uploaded_docs,
      'signatureRequirements'  => $profile->getSignaturesMap(),
      'signatures'             => $profile->loadSignedLegalpadMonograms(),
      'selectedCoursePath'     => $has_coursepath
                      ? array(
                        'phid' => head($selected_coursepath)->getPHID(),
                        'name' => head($selected_coursepath)->getName(),
                      )
                      : array(),
      'selectedCoursePaths'    => $coursepath_results,
      'upFor'       => $is_finished_onboarding
                      ? $profile->getUpFor()
                      : '',
      'options'                 => array(
        'legalDocuments'        => SuiteProfile::getTNCMap(),
        'uploads'               => SuiteProfile::getDocsMap(),
        'upFor'                 => SuiteProfile::getUpForMap(),
        'graduationTargetMonth' => SuiteProfile::getCommitmentMap(),
      ),
      'dateCreated' => $profile->getDateCreated(),
      'dateModified' => $profile->getDateModified(),
      'cv'          => $profile->getCv() ? $profile->getCv() : null,
      'uri'         => PhabricatorEnv::getProductionURI(
        '/suite/users/view/'.$user->getID()),
    );

    return $result;
  }

  protected function getFile($viewer, $phid) {
    $image_url = null;
    $file = id(new PhabricatorFileQuery())
      ->setViewer($viewer)
      ->withPHIDs(array($phid))
      ->executeOne();

    if ($file) {
      $image_url = $file->getBestURI();
    }

    return $image_url;
  }

}
