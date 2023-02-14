<?php

final class MentionEditEngine
  extends PhabricatorEditEngine {

  const ENGINECONST = 'mention';

  public function getEngineApplicationClass() {
    return 'PhabricatorMentionApplication';
  }

  protected function buildCustomEditFields($object) {
    $viewer = $this->getViewer();

    if ($this->getIsCreate()) {
      $participant_phids = array($viewer->getPHID());
      $initial_phids = array();
    } else {
      $participant_phids = $object->getParticipantPHIDs();
      $initial_phids = $participant_phids;
    }

    // Only show participants on create or conduit, not edit.
    $show_participants = (bool)$this->getIsCreate();

    return array(
      id(new PhabricatorTextEditField())
        ->setKey('name')
        ->setLabel(pht('Name'))
        ->setDescription(pht('Room name.'))
        ->setConduitTypeDescription(pht('New Room name.'))
        ->setIsRequired(true)
        ->setTransactionType(
          ConpherenceThreadTitleTransaction::TRANSACTIONTYPE)
        ->setValue($object->getTitle()),

      id(new PhabricatorTextEditField())
        ->setKey('topic')
        ->setLabel(pht('Topic'))
        ->setDescription(pht('Room topic.'))
        ->setConduitTypeDescription(pht('New Room topic.'))
        ->setTransactionType(
          ConpherenceThreadTopicTransaction::TRANSACTIONTYPE)
        ->setValue($object->getTopic()),

      id(new PhabricatorUsersEditField())
        ->setKey('participants')
        ->setValue($participant_phids)
        ->setInitialValue($initial_phids)
        ->setIsFormField($show_participants)
        ->setAliases(array('users', 'members', 'participants', 'userPHID'))
        ->setDescription(pht('Room participants.'))
        ->setUseEdgeTransactions(true)
        ->setConduitTypeDescription(pht('New Room participants.'))
        ->setTransactionType(
          ConpherenceThreadParticipantsTransaction::TRANSACTIONTYPE)
        ->setLabel(pht('Initial Participants')),

    );
  }

  public function getEngineName() {
    return pht('Mention');
  }

  protected function getObjectCreateTitleText($object) {
    return pht('Create New Mention');
  }

  protected function willBuildEditForm($object, array $fields) {
    return false;
  }

  protected function getObjectCreateShortText() {
    return pht('Create Room');
  }

  protected function getObjectName() {
    return pht('Mention');
  }

  protected function getObjectEditShortText($object) {
    return $object->getMessage();
  }

  public function getSummaryHeader() {
    return pht('Configure Mention Forms');
  }

  public function getSummaryText() {
    return pht('Configure creation and editing forms in Mention.');
  }

  protected function getObjectViewURI($object) {
    return $object->getURI();
  }

  protected function newEditableObject() {
    return PhabricatorMention::initializeNewItem($this->getViewer());
  }

  protected function newObjectQuery() {
    return new PhabricatorMentionQuery();
  }

  protected function getObjectEditTitleText($object) {
    return pht('Edit Room: %s', $object->getTitle());
  }
}
