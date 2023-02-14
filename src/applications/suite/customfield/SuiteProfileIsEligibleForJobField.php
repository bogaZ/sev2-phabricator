<?php

final class SuiteProfileIsEligibleForJobField
  extends SuiteProfileCustomField {

  private $value;

  public function getFieldKey() {
    return 'suite_profile:isEligibleForJob';
  }

  public function getModernFieldKey() {
    return 'isEligibleForJob';
  }

  public function getFieldKeyForConduit() {
    return $this->getModernFieldKey();
  }

  public function getFieldName() {
    return pht('Eligible for job');
  }

  public function getFieldDescription() {
    return pht('Job eligibility status.');
  }

  public function canDisableField() {
    return false;
  }

  public function shouldAppearInApplicationTransactions() {
    return true;
  }

  public function shouldAppearInEditView() {
    return true;
  }

  public function shouldAppearInPropertyView() {
    return false;
  }

  public function isFieldEnabled() {
    return true;
  }

  public function readValueFromObject(PhabricatorCustomFieldInterface $object) {
    $this->value = (bool)$object->getIsEligibleForJob();
  }

  public function getOldValueForApplicationTransactions() {
    return $this->getObject()->getIsEligibleForJob();
  }

  public function getNewValueForApplicationTransactions() {
    return (bool)$this->value;
  }

  public function applyApplicationTransactionInternalEffects(
    PhabricatorApplicationTransaction $xaction) {
    $this->getObject()->setIsEligibleForJob((int)$xaction->getNewValue());
  }

  public function readValueFromRequest(AphrontRequest $request) {
    $this->value = $request->getBool($this->getFieldKey());
  }

  public function setValueFromStorage($value) {
    $this->value = (bool)$value;
    return $this;
  }

  public function renderEditControl(array $handles) {
    return id(new AphrontFormSelectControl())
      ->setName($this->getFieldKey())
      ->setValue($this->value)
      ->setOptions(array(
        true => 'Yes',
        false => 'No',
      ))
      ->setLabel($this->getFieldName())
      ->setDisabled(!$this->isEditable());
  }

  public function shouldAppearInConduitTransactions() {
    return true;
  }

  protected function newConduitEditParameterType() {
    return new ConduitBoolParameterType();
  }

}
