<?php

final class PhabricatorCoursepathItemTestEditEngine
  extends PhabricatorEditEngine {

  const ENGINECONST = 'coursepath.item.test';

  public function getEngineName() {
    return pht('Skill Test');
  }

  public function getEngineApplicationClass() {
    return 'PhabricatorCoursepathApplication';
  }

  public function getSummaryHeader() {
    return pht('Configure Test Forms');
  }

  public function getSummaryText() {
    return pht('Configure creation and editing forms in Test.');
  }

  public function isEngineConfigurable() {
    return false;
  }

  protected function newEditableObject() {
    $controller = $this->getController();
    $item_phid = '';
    if ($controller) {
      $request = $controller->getRequest();
      $item_phid = $request->getStr('itemPHID');
    }

    return CoursepathItemTest::initializeNewTest(
      $this->getViewer(),
      $item_phid);
  }

  protected function newObjectQuery() {
    return new CoursepathItemTestQuery();
  }

  protected function getObjectCreateTitleText($object) {
    return pht('Create New Test');
  }

  protected function getObjectEditTitleText($object) {
    return pht('Edit / Create Test: %s', $object->getTitle());
  }

  protected function getObjectEditShortText($object) {
    return $object->getTitle();
  }

  protected function getObjectCreateShortText() {
    return pht('Create Skill Test');
  }

  protected function getObjectName() {
    return pht('Skill Test');
  }

  protected function getObjectCreateCancelURI($object) {
    return $this->getApplication()->getApplicationURI('tests/');
  }

  protected function getEditorURI() {
    return $this->getApplication()->getApplicationURI('tests/create');
  }

  protected function getCommentViewHeaderText($object) {
    return pht('Render Honors');
  }

  protected function getCommentViewButtonText($object) {
    return pht('Salute');
  }

  protected function getObjectViewURI($object) {
    $item = id(new CoursepathItemQuery())
        ->setViewer($this->getViewer())
        ->withPHIDs(array($object->getItemPHID()))
        ->executeOne();
    if (!$item) {
      return new Aphront404Response();
    }
    return $object->getViewURI($item->getID());
  }

  protected function getCreateNewObjectPolicy() {
    return $this->getApplication()->getPolicy(
      CoursepathManageCapability::CAPABILITY);
  }

  protected function buildCustomEditFields($object) {

    return array(
      id(new PhabricatorTextEditField())
        ->setKey('title')
        ->setLabel(pht('Title'))
        ->setDescription(pht('Skill test title.'))
        ->setConduitTypeDescription(pht('New skill test title.'))
        ->setTransactionType(
          CoursepathItemTestTitleTransaction::TRANSACTIONTYPE)
        ->setValue($object->getTitle())
        ->setIsRequired(true),
      id(new PhabricatorRemarkupEditField())
        ->setKey('question')
        ->setLabel(pht('Question'))
        ->setDescription(pht('Question long text.'))
        ->setConduitTypeDescription(pht('New skill test question.'))
        ->setTransactionType(
          CoursepathItemTestQuestionTransaction::TRANSACTIONTYPE)
        ->setValue($object->getQuestion()),
      id(new PhabricatorRemarkupEditField())
        ->setKey('answer')
        ->setLabel(pht('Answer'))
        ->setDescription(pht('Answer long text.'))
        ->setConduitTypeDescription(pht('New skill test answer.'))
        ->setTransactionType(
          CoursepathItemTestAnswerTransaction::TRANSACTIONTYPE)
        ->setValue($object->getAnswer()),
      id(new PhabricatorSelectEditField())
        ->setKey('type')
        ->setLabel(pht('Type'))
        ->setTransactionType(
          CoursepathItemTestTypeTransaction::TRANSACTIONTYPE)
        ->setDescription(pht('Skill test type'))
        ->setValue('')
        ->setOptions($object->getTypeMap()),
      id(new PhabricatorSelectEditField())
        ->setKey('severity')
        ->setLabel(pht('Severity'))
        ->setTransactionType(
          CoursepathItemTestSeverityTransaction::TRANSACTIONTYPE)
        ->setDescription(pht('Skill test difficulty'))
        ->setValue('')
        ->setOptions($object->getSeverityMap()),
      id(new PhabricatorTextEditField())
        ->setKey('itemPHID')
        ->setLabel(pht('itemPHID *do not edit'))
        ->setDescription(pht('Skill test title.'))
        ->setConduitTypeDescription(pht('New skill test title.'))
        ->setTransactionType(
          CoursepathItemTestItemPHIDTransaction::TRANSACTIONTYPE)
        ->setValue($object->getItemPHID())
        ->setIsPreview(false),
    );
  }

}
