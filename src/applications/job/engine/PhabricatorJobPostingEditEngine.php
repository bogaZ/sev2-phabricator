<?php

final class PhabricatorJobPostingEditEngine
  extends PhabricatorEditEngine {

  const ENGINECONST = 'job.edit';

  public function getEngineName() {
    return pht('Job Posting');
  }

  public function getEngineApplicationClass() {
    return 'PhabricatorJobApplication';
  }

  public function getSummaryHeader() {
    return pht('Configure Job Posting Forms');
  }

  public function getSummaryText() {
    return pht('Configure creation and editing forms in Job Posting.');
  }

  public function isEngineConfigurable() {
    return false;
  }

  protected function newEditableObject() {
    return JobPosting::initializeNewItem($this->getViewer());
  }

  protected function newObjectQuery() {
    return new JobPostingQuery();
  }

  protected function getObjectCreateTitleText($object) {
    return pht('Create New Job Posting');
  }

  protected function getObjectEditTitleText($object) {
    return pht('Edit Job Posting: %s', $object->getName());
  }

  protected function getObjectEditShortText($object) {
    return $object->getName();
  }

  protected function getObjectCreateShortText() {
    return pht('Create Job Posting');
  }

  protected function getObjectName() {
    return pht('Job Posting');
  }

  protected function getObjectCreateCancelURI($object) {
    return $this->getApplication()->getApplicationURI('/');
  }

  protected function getEditorURI() {
    return $this->getApplication()->getApplicationURI('edit/');
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
      JobManageCapability::CAPABILITY);
  }

  protected function buildCustomEditFields($object) {

    return array(
      id(new PhabricatorTextEditField())
        ->setKey('name')
        ->setLabel(pht('Name'))
        ->setDescription(pht('Job posting name.'))
        ->setConduitTypeDescription(pht('New job posting name.'))
        ->setTransactionType(
          JobPostingNameTransaction::TRANSACTIONTYPE)
        ->setValue($object->getName())
        ->setIsRequired(true),
      id(new PhabricatorTextEditField())
        ->setKey('location')
        ->setLabel(pht('Job Location'))
        ->setDescription(pht('Job Location.'))
        ->setConduitTypeDescription(pht('New Job Location.'))
        ->setTransactionType(
          JobPostingLocationTransaction::TRANSACTIONTYPE)
        ->setValue($object->getLocation())
        ->setIsRequired(true),
      id(new PhabricatorTextEditField())
        ->setKey('targetHiring')
        ->setLabel(pht('Target Hiring (Person)'))
        ->setDescription(pht('Target Hiring'))
        ->setConduitTypeDescription(pht('New Target Hiring.'))
        ->setTransactionType(
          JobPostingTargetHiringTransaction::TRANSACTIONTYPE)
        ->setValue($object->getTargetHiring()),
      id(new PhabricatorTextEditField())
        ->setKey('business')
        ->setLabel(pht('Business Type'))
        ->setDescription(pht('Business Type'))
        ->setConduitTypeDescription(pht('Business Type.'))
        ->setTransactionType(
          JobPostingBusinessTransaction::TRANSACTIONTYPE)
        ->setValue($object->getBusiness()),
      id(new PhabricatorSelectEditField())
        ->setKey('salaryCurrency')
        ->setLabel(pht('Salary Currency'))
        ->setDescription(pht('Salary currency'))
        ->setConduitTypeDescription(pht('New job posting start from.'))
        ->setTransactionType(
          JobPostingSalaryCurrencyTransaction::TRANSACTIONTYPE)
        ->setOptions(JobPosting::getCurrencyMap())
        ->setValue($object->getSalaryCurrency())
        ->setIsRequired(true),
      id(new PhabricatorIntEditField())
        ->setKey('salaryFrom')
        ->setLabel(pht('Salary Start From'))
        ->setDescription(pht('Salary start from.'))
        ->setConduitTypeDescription(pht('New job posting start from.'))
        ->setTransactionType(
          JobPostingSalaryFromTransaction::TRANSACTIONTYPE)
        ->setValue($object->getSalaryFrom())
        ->setIsRequired(true),
      id(new PhabricatorIntEditField())
        ->setKey('salaryTo')
        ->setLabel(pht('Salary End To'))
        ->setDescription(pht('Salary end to.'))
        ->setConduitTypeDescription(pht('New job posting end to.'))
        ->setTransactionType(
          JobPostingSalaryToTransaction::TRANSACTIONTYPE)
        ->setValue($object->getSalaryTo())
        ->setIsRequired(true),
      id(new PhabricatorBoolEditField())
        ->setIsLockable(false)
        ->setIsDefaultable(false)
        ->setKey('isLead')
        ->setOptions(pht('Confirmed'), pht('Still a Lead'))
        ->setAsCheckbox(true)
        ->setTransactionType(
          JobPostingIsLeadTransaction::TRANSACTIONTYPE)
        ->setDescription(pht('Marks this as a lead posting.'))
        ->setConduitDescription(pht('Make the job posting a lead posting.'))
        ->setConduitTypeDescription(pht('Mark the job posting as a lead posting.'))
        ->setValue($object->getIsLead()),
      id(new PhabricatorEpochEditField())
        ->setKey('start')
        ->setLabel(pht('Start'))
        ->setIsLockable(false)
        ->setIsDefaultable(false)
        ->setTransactionType(
          JobPostingStartDateTransaction::TRANSACTIONTYPE)
        ->setDescription(pht('Start time of the job posting.'))
        ->setConduitDescription(pht('Change the start time of the job posting.'))
        ->setConduitTypeDescription(pht('New job posting start time.'))
        ->setValue($object->getStartDateTimeEpoch()),
      id(new PhabricatorEpochEditField())
        ->setKey('end')
        ->setLabel(pht('End'))
        ->setIsLockable(false)
        ->setIsDefaultable(false)
        ->setTransactionType(
          JobPostingEndDateTransaction::TRANSACTIONTYPE)
        ->setDescription(pht('End time of the job posting.'))
        ->setConduitDescription(pht('Change the end time of the job posting.'))
        ->setConduitTypeDescription(pht('New job posting end time.'))
        ->setValue($object->newEndDateTimeForEdit()->getEpoch()),
      id(new PhabricatorIconSetEditField())
        ->setKey('icon')
        ->setLabel(pht('Icon'))
        ->setIconSet(new JobPostingIconSet())
        ->setTransactionType(
          JobPostingIconTransaction::TRANSACTIONTYPE)
        ->setDescription(pht('Job icon.'))
        ->setConduitDescription(pht('Change the job icon.'))
        ->setConduitTypeDescription(pht('New job icon.'))
        ->setValue($object->getIcon()),
      id(new PhabricatorRemarkupEditField())
        ->setKey('description')
        ->setLabel(pht('Description'))
        ->setDescription(pht('Job posting long description.'))
        ->setConduitTypeDescription(pht('New job posting description.'))
        ->setTransactionType(
          JobPostingDescriptionTransaction::TRANSACTIONTYPE)
        ->setValue($object->getDescription()),
      id(new PhabricatorTextAreaEditField())
        ->setKey('benefit')
        ->setLabel(pht('Benefit'))
        ->setDescription(pht('Job posting benefit'))
        ->setConduitTypeDescription(pht('New job posting benefit.'))
        ->setTransactionType(
          JobPostingBenefitTransaction::TRANSACTIONTYPE)
        ->setValue($object->getBenefit()),
      id(new PhabricatorTextAreaEditField())
        ->setKey('perk')
        ->setLabel(pht('Perk'))
        ->setDescription(pht('Job posting perk'))
        ->setConduitTypeDescription(pht('New job posting perk.'))
        ->setTransactionType(
          JobPostingPerkTransaction::TRANSACTIONTYPE)
        ->setValue($object->getPerk()),
      id(new PhabricatorTextEditField())
        ->setKey('stack')
        ->setLabel(pht('Stack Technology'))
        ->setDescription(pht('Stack Technology'))
        ->setConduitTypeDescription(pht('Stack Technology.'))
        ->setTransactionType(
          JobPostingStackTransaction::TRANSACTIONTYPE)
        ->setValue($object->getStack())
        ->setIsRequired(true),
      id(new PhabricatorBoolEditField())
        ->setKey('cancelled')
        ->setLabel('Cancel')
        ->setOptions(pht('Active'), pht('Cancelled'))
        ->setLabel(pht('Cancelled'))
        ->setDescription(pht('Cancel the job posting.'))
        ->setTransactionType(
          JobPostingCancelTransaction::TRANSACTIONTYPE)
        ->setIsFormField(false)
        ->setConduitDescription(pht('Cancel or restore the job posting.'))
        ->setConduitTypeDescription(pht('True to cancel the job posting.'))
        ->setValue($object->getIsCancelled()),
    );
  }

}
