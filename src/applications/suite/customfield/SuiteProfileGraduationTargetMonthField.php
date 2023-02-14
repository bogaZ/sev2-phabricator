<?php

final class SuiteProfileGraduationTargetMonthField
  extends SuiteProfileCustomField {

  private $value;

  public function getFieldKey() {
    return 'suite_profile:graduationTargetMonth';
  }

  public function getModernFieldKey() {
    return 'graduationTargetMonth';
  }

  public function getFieldKeyForConduit() {
    return $this->getModernFieldKey();
  }

  public function getFieldName() {
    return pht('Initial Commitment');
  }

  public function getFieldDescription() {
    return pht('Shows how many months user are willing to get the result.');
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
    $this->value = $object->getGraduationTargetMonth();
  }

  public function getOldValueForApplicationTransactions() {
    return $this->getObject()->getGraduationTargetMonth();
  }

  public function getNewValueForApplicationTransactions() {
    return (int)$this->value;
  }

  public function applyApplicationTransactionInternalEffects(
    PhabricatorApplicationTransaction $xaction) {
    $this->getObject()->setGraduationTargetMonth((int)$xaction->getNewValue());
  }

  public function readValueFromRequest(AphrontRequest $request) {
    $this->value = $request->getInt($this->getFieldKey());
  }

  public function setValueFromStorage($value) {
    $this->value = (int)$value;
    return $this;
  }

  public function renderEditControl(array $handles) {
    return id(new AphrontFormSelectControl())
      ->setName($this->getFieldKey())
      ->setValue($this->value)
      ->setOptions(array(
        '3' => '3 months',
        '4' => '4 months',
        '5' => '5 months',
        '6' => '6 months',
      ))
      ->setLabel($this->getFieldName())
      ->setDisabled(!$this->isEditable());
  }

  public function shouldAppearInConduitTransactions() {
    return true;
  }

  protected function newConduitEditParameterType() {
    return new ConduitIntParameterType();
  }

}
