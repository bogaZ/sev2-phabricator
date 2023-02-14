<?php

final class PhabricatorCoursepathItemEditEngine
  extends PhabricatorEditEngine {

  const ENGINECONST = 'coursepath.item';

  public function getEngineName() {
    return pht('Course Path');
  }

  public function getEngineApplicationClass() {
    return 'PhabricatorCoursepathApplication';
  }

  public function getSummaryHeader() {
    return pht('Configure Course Path Forms');
  }

  public function getSummaryText() {
    return pht('Configure creation and editing forms in Course Path.');
  }

  public function isEngineConfigurable() {
    return false;
  }

  protected function newEditableObject() {
    return CoursepathItem::initializeNewItem($this->getViewer());
  }

  protected function newObjectQuery() {
    return new CoursepathItemQuery();
  }

  protected function getObjectCreateTitleText($object) {
    return pht('Create New Course Path');
  }

  protected function getObjectEditTitleText($object) {
    return pht('Edit Course Path: %s', $object->getName());
  }

  protected function getObjectEditShortText($object) {
    return $object->getName();
  }

  protected function getObjectCreateShortText() {
    return pht('Create Course Path');
  }

  protected function getObjectName() {
    return pht('Course Path');
  }

  protected function getObjectCreateCancelURI($object) {
    return $this->getApplication()->getApplicationURI('item/');
  }

  protected function getEditorURI() {
    return $this->getApplication()->getApplicationURI('item/edit/');
  }

  protected function getCommentViewHeaderText($object) {
    return pht('Render Honors');
  }

  protected function getCommentViewButtonText($object) {
    return pht('Salute');
  }

  protected function getObjectViewURI($object) {
    return $object->getViewURI();
  }

  protected function getCreateNewObjectPolicy() {
    return $this->getApplication()->getPolicy(
      CoursepathManageCapability::CAPABILITY);
  }

  protected function buildCustomEditFields($object) {

    return array(
      id(new PhabricatorTextEditField())
        ->setKey('name')
        ->setLabel(pht('Name'))
        ->setDescription(pht('Course path name.'))
        ->setConduitTypeDescription(pht('New course path name.'))
        ->setTransactionType(
          CoursepathItemNameTransaction::TRANSACTIONTYPE)
        ->setValue($object->getName())
        ->setIsRequired(true),
      id(new PhabricatorRemarkupEditField())
        ->setKey('description')
        ->setLabel(pht('Description'))
        ->setDescription(pht('Course path long description.'))
        ->setConduitTypeDescription(pht('New course path description.'))
        ->setTransactionType(
          CoursepathItemDescriptionTransaction::TRANSACTIONTYPE)
      ->setValue($object->getDescription()),
        id(new PhabricatorTextEditField())
        ->setKey('slug')
        ->setLabel(pht('Slug'))
        ->setDescription(pht('Slug for coursepath.'))
        ->setConduitTypeDescription(pht('New course path slug.'))
        ->setTransactionType(
          CoursepathItemSlugTransaction::TRANSACTIONTYPE)
        ->setValue($object->getSlug()),
    );
  }

}
