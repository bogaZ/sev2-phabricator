<?php

final class SuiteProfileUpForField
  extends SuiteProfileCustomField {

  private $value;

  public function getFieldKey() {
    return 'suite_profile:upFor';
  }

  public function getModernFieldKey() {
    return 'upFor';
  }

  public function getFieldKeyForConduit() {
    return $this->getModernFieldKey();
  }

  public function getFieldName() {
    return pht('Interest');
  }

  public function getFieldDescription() {
    return pht('Shows user interest.');
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
    $this->value = $object->getUpFor();
  }

  public function getOldValueForApplicationTransactions() {
    return $this->getObject()->getUpFor();
  }

  public function getNewValueForApplicationTransactions() {
    return $this->value;
  }

  public function applyApplicationTransactionInternalEffects(
    PhabricatorApplicationTransaction $xaction) {
    $this->getObject()->setUpFor($xaction->getNewValue());
  }

  public function readValueFromRequest(AphrontRequest $request) {
    $this->value = $request->getStr($this->getFieldKey());
  }

  public function setValueFromStorage($value) {
    $this->value = $value;
    return $this;
  }

  public function renderEditControl(array $handles) {
    return id(new AphrontFormSelectControl())
      ->setName($this->getFieldKey())
      ->setValue($this->value)
      ->setOptions(array(
        'undefined' => 'Has not selected',
        'work' => 'Get hired',
        'rsp' => 'Work from home',
        'upskill' => 'Just want to hang around',
      ))
      ->setLabel($this->getFieldName())
      ->setDisabled(!$this->isEditable());
  }

  public function shouldAppearInConduitTransactions() {
    return true;
  }

  protected function newConduitEditParameterType() {
    return new ConduitStringParameterType();
  }

}
