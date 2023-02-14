<?php

final class SuiteProfileEditEngine
  extends PhabricatorEditEngine {

  const ENGINECONST = 'suite_profile.edit';

  public function getEngineName() {
    return pht('Suite Profile');
  }

  public function getEngineApplicationClass() {
    return 'PhabricatorSuiteApplication';
  }

  public function getSummaryHeader() {
    return pht('Configure Suite Profile Posting Forms');
  }

  public function getSummaryText() {
    return pht('Configure editing forms in Suite Profile.');
  }

  public function isEngineConfigurable() {
    return false;
  }

  public function isEngineExtensible() {
    return false;
  }

  protected function newEditableObject() {
    return new SuiteProfile();
  }

  protected function newObjectQuery() {
    return new SuiteProfileQuery();
  }

  protected function getObjectCreateTitleText($object) {
    return pht('You could not create new profile');
  }

  protected function getObjectEditTitleText($object) {
    return pht('Edit profile: %s', $object->getOwnerPHID());
  }

  protected function getObjectEditShortText($object) {
    return $object->getOwnerPHID();
  }

  protected function getObjectCreateShortText() {
    return pht('You could not create new profile');
  }

  protected function getObjectName() {
    return pht('Suite Profile');
  }

  protected function getObjectCreateCancelURI($object) {
    return $this->getApplication()->getApplicationURI('users/');
  }

  protected function getEditorURI() {
    return $this->getApplication()->getApplicationURI('users/edit/');
  }

  protected function getObjectViewURI($object) {
    return $object->getViewURI();
  }

  protected function getCreateNewObjectPolicy() {
    return $this->getApplication()->getPolicy(
      PhabricatorSuiteCapabilityManageUser::CAPABILITY);
  }

  protected function buildCustomEditFields($object) {

    return array(
      id(new PhabricatorSelectEditField())
        ->setKey('upFor')
        ->setLabel(pht('Up For'))
        ->setDescription(pht('User interest'))
        ->setConduitTypeDescription(pht('User interest.'))
        ->setTransactionType(
          SuiteProfileUpForTransaction::TRANSACTIONTYPE)
        ->setOptions(SuiteProfile::getUpForMap())
        ->setValue($object->getUpFor())
        ->setIsRequired(true),
      id(new PhabricatorSelectEditField())
        ->setKey('graduationTargetMonth')
        ->setLabel(pht('Initial commitment'))
        ->setDescription(pht('User commitment on how long '.
          'they want to get the result'))
        ->setConduitTypeDescription(pht('User commitment on how long '.
          'they want to get the result'))
        ->setTransactionType(
          SuiteProfileGraduationTargetMonthTransaction::TRANSACTIONTYPE)
        ->setOptions(SuiteProfile::getCommitmentMap())
        ->setValue($object->getUpFor())
        ->setIsRequired(true),
    );
  }
}
