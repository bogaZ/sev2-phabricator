<?php

final class PhabricatorUserPhoneNumberField
  extends PhabricatorUserCustomField {

  private $value;

  public function getFieldKey() {
    return 'user:phonenumber';
  }

  public function getModernFieldKey() {
    return 'phoneNumber';
  }

  public function getFieldKeyForConduit() {
    return $this->getModernFieldKey();
  }

  public function getFieldName() {
    return pht('Phone Number');
  }

  public function getFieldDescription() {
    return pht('Stores phone number of the user, like "0813221233"');
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

  public function readValueFromObject(PhabricatorCustomFieldInterface $object) {
    $this->value = $object->loadUserProfile()->getPhoneNumber();
  }

  public function getOldValueForApplicationTransactions() {
    return $this->getObject()->loadUserProfile()->getPhoneNumber();
  }

  public function getNewValueForApplicationTransactions() {
    if (!$this->isEditable()) {
      return $this->getObject()->loadUserProfile()->getPhoneNumber();
    }
    return $this->value;
  }

  public function applyApplicationTransactionInternalEffects(
    PhabricatorApplicationTransaction $xaction) {
    $new_value = $xaction->getNewValue();
    $this->getObject()->loadUserProfile()->setPhoneNumber($new_value);
  }

  public function readValueFromRequest(AphrontRequest $request) {
    $this->value = $request->getStr($this->getFieldKey());
  }

  public function setValueFromStorage($value) {
    $this->value = $value;
    return $this;
  }

  public function renderEditControl(array $handles) {
    return id(new AphrontFormTextControl())
      ->setName($this->getFieldKey())
      ->setValue($this->value)
      ->setLabel($this->getFieldName())
      ->setDisabled(!$this->isEditable());
  }

  private function isEditable() {
    return PhabricatorEnv::getEnvConfig('account.editable');
  }

  public function shouldAppearInConduitTransactions() {
    return true;
  }

  protected function newConduitEditParameterType() {
    return new ConduitStringParameterType();
  }

}
