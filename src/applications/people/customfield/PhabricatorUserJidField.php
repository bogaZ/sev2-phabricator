<?php

final class PhabricatorUserJidField
  extends PhabricatorUserCustomField {

  private $value;

  public function getFieldKey() {
    return 'user:jid';
  }

  public function getModernFieldKey() {
    return 'jid';
  }

  public function getFieldKeyForConduit() {
    return $this->getModernFieldKey();
  }

  public function getFieldName() {
    return pht('Jid');
  }

  public function getFieldDescription() {
    return pht('Stores the Jabber ID like: username@xmpp.sev-2.com".');
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
    $this->value = $object->getJid();
  }

  public function getOldValueForApplicationTransactions() {
    return $this->getObject()->getJid();
  }

  public function getNewValueForApplicationTransactions() {
    if (!$this->isEditable()) {
      return $this->getObject()->getJid();
    }
    return $this->value;
  }

  public function applyApplicationTransactionInternalEffects(
    PhabricatorApplicationTransaction $xaction) {
    $this->getObject()->setJid($xaction->getNewValue());
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
