<?php

final class SuiteProfileIsRspField
  extends SuiteProfileCustomField {

  private $value;
  private $request;

  public function getFieldKey() {
    return 'suite_profile:isRsp';
  }

  public function getModernFieldKey() {
    return 'isRsp';
  }

  public function getFieldKeyForConduit() {
    return $this->getModernFieldKey();
  }

  public function getFieldName() {
    return pht('Is RSP');
  }

  public function getFieldDescription() {
    return pht('RSP status.');
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
    $this->value = (bool)$object->getIsRsp();
  }

  public function getOldValueForApplicationTransactions() {
    return $this->getObject()->getIsRsp();
  }

  public function getNewValueForApplicationTransactions() {
    if ($this->value) {
      $this->assignToRSPOrg();
    } else {
      $this->removeFromRSPOrg();
    }

    return (bool)$this->value;
  }

  public function applyApplicationTransactionInternalEffects(
    PhabricatorApplicationTransaction $xaction) {
    $this->getObject()->setIsRsp((int)$xaction->getNewValue());
  }

  public function readValueFromRequest(AphrontRequest $request) {
    $this->request = $request;
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

  protected function assignToRSPOrg() {
    $selected_member = $this->getObject()->getOwnerPHID();
    $project = id(new PhabricatorProjectQuery())
      ->setViewer($this->getViewer())
      ->withNames(array('RSP'))
      ->executeOne();

    if ($project) {
      $xactions = array();

      $type_member = PhabricatorProjectProjectHasMemberEdgeType::EDGECONST;

      $xactions[] = id(new PhabricatorProjectTransaction())
      ->setTransactionType(PhabricatorTransactions::TYPE_EDGE)
      ->setMetadataValue('edge:type', $type_member)
      ->setNewValue(
        array(
          '+' => array($selected_member => $selected_member),
        ));

      $editor = id(new PhabricatorProjectTransactionEditor())
        ->setActor($this->getViewer())
        ->setContinueOnNoEffect(true)
        ->setContentSourceFromRequest($this->request)
        ->applyTransactions($project, $xactions);
    }
  }

  protected function removeFromRSPOrg() {
    $selected_member = $this->getObject()->getOwnerPHID();
    $project = id(new PhabricatorProjectQuery())
      ->setViewer($this->getViewer())
      ->withNames(array('RSP'))
      ->executeOne();

    if ($project) {
      $xactions = array();

      $edge_type = PhabricatorProjectProjectHasMemberEdgeType::EDGECONST;
      $xactions[] = id(new PhabricatorProjectTransaction())
      ->setTransactionType(PhabricatorTransactions::TYPE_EDGE)
      ->setMetadataValue('edge:type', $edge_type)
      ->setNewValue(
        array(
          '-' => array($selected_member => $selected_member),
        ));

      $editor = id(new PhabricatorProjectTransactionEditor())
        ->setActor($this->getViewer())
        ->setContinueOnNoEffect(true)
        ->setContentSourceFromRequest($this->request)
        ->applyTransactions($project, $xactions);
    }
  }

}
