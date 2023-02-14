<?php

final class LobbyGoalsEditEngine
  extends PhabricatorEditEngine {

  const ENGINECONST = 'lobby.goals';

  public function getEngineName() {
    return pht('Lobby Goals');
  }

  public function getEngineApplicationClass() {
    return 'PhabricatorLobbyApplication';
  }

  public function getSummaryHeader() {
    return pht('Configure Goals Forms');
  }

  public function getSummaryText() {
    return pht('Configure creation and editing forms in Goals.');
  }

  public function isEngineConfigurable() {
    return false;
  }

  protected function newEditableObject() {
    return LobbyStickit::initializeNewItem($this->getViewer());
  }

  protected function newObjectQuery() {
    return new LobbyStickitQuery();
  }

  protected function getObjectCreateTitleText($object) {
    return pht('Add Goals');
  }

  protected function getObjectEditTitleText($object) {
    return pht('Edit Goals: %s', $object->getTitle());
  }

  protected function getObjectEditShortText($object) {
    return $object->getTitle();
  }

  protected function getObjectCreateShortText() {
    return pht('Add Goals');
  }

  protected function getObjectName() {
    return pht('Lobby Goals');
  }

  protected function getObjectCreateCancelURI($object) {
    return $this->getApplication()->getApplicationURI('goals/');
  }

  protected function getEditorURI() {
    return $this->getApplication()->getApplicationURI('goals/edit/');
  }

  protected function getCommentViewHeaderText($object) {
    return pht('Render Honors');
  }

  protected function getCommentViewButtonText($object) {
    return pht('Salute');
  }

  protected function getObjectViewURI($object) {
    return '/lobby/goals/'.$object->getID().'/';
  }

  protected function getCreateNewObjectPolicy() {
    return $this->getApplication()->getPolicy(
      LobbyManageCapability::CAPABILITY);
  }

  protected function buildCustomEditFields($object) {
    $task_phids = array();
    $options = [
      0 => pht('Incomplete'),
      1 => pht('Complete'),
    ];
    $progress_range = range(0, 100, 10);
    $progress_map = array_combine($progress_range, $progress_range);
    $lobby_type = LobbyGoalsHasManiphestEdgeType::EDGECONST;

    if ($object->getPHID()) {
      $task_phids = PhabricatorEdgeQuery::loadDestinationPHIDs(
        $object->getPHID(),
        $lobby_type);
    }

    $fields = array(
      id(new PhabricatorTextEditField())
        ->setKey('noteType')
        ->setLabel(pht('Type'))
        ->setBulkEditLabel(pht('Set type to'))
        ->setDescription(pht('Type of the goals.'))
        ->setConduitDescription(pht('Change the goals type.'))
        ->setConduitTypeDescription(pht('New goals type.'))
        ->setTransactionType(LobbyStickitNoteTypeTransaction::TRANSACTIONTYPE)
        ->setIsCopyable(true)
        ->setIsHidden(true)
        ->setValue('goals')
        ->setCommentActionLabel(pht('Change Type'))
        ->setCommentActionValue('goals'),
      id(new PhabricatorTextEditField())
        ->setKey('title')
        ->setLabel(pht('Title'))
        ->setDescription(pht('Title.'))
        ->setPlaceholder(pht('Project name - Main goal (max 4 words)'))
        ->setConduitTypeDescription(pht('New goals title.'))
        ->setValue($object->getTitle())
        ->setIsRequired(true)
        ->setTransactionType(
          LobbyStickitTitleTransaction::TRANSACTIONTYPE),
      id(new PhabricatorRemarkupEditField())
        ->setKey('content')
        ->setLabel(pht('Content'))
        ->setDescription(pht('The content of the goals.'))
        ->setConduitTypeDescription(pht('New goals content.'))
        ->setValue($object->getContent())
        ->setTransactionType(
          LobbyStickitContentTransaction::TRANSACTIONTYPE),
      id(new PhabricatorRemarkupEditField())
        ->setKey('blocked')
        ->setLabel(pht('Blocker'))
        ->setDescription(pht('The reason goals did not finished.'))
        ->setConduitTypeDescription(pht('New goals blocker.'))
        ->setValue($object->getDescription())
        ->setTransactionType(
          LobbyStickitBlockedTransaction::TRANSACTIONTYPE),
      id(new PhabricatorRemarkupEditField())
        ->setKey('message')
        ->setLabel(pht('Action Items'))
        ->setDescription(pht('The action item how goals finished.'))
        ->setConduitTypeDescription(pht('Goals action items.'))
        ->setValue($object->getMessage())
        ->setTransactionType(
          LobbyStickitMessageTransaction::TRANSACTIONTYPE),
      id(new PhabricatorManiphestsEditField())
        ->setKey('maniphest')
        ->setLabel(pht('Maniphest'))
        ->setEditTypeKey('maniphest')
        ->setAliases(array('maniphest', 'maniphests', 'task', 'tasks'))
        ->setIsCopyable(true)
        ->setConduitDescription(pht('Associate between goals and maniphest'))
        ->setConduitTypeDescription(
          pht('Associate between goals and maniphest.'))
        ->setUseEdgeTransactions(true)
        ->setCommentActionLabel(pht('Add maniphest task'))
        ->setCommentActionOrder(8000)
        ->setDescription(pht('Select task for the goals.'))
        ->setTransactionType(PhabricatorTransactions::TYPE_EDGE)
        ->setMetadataValue('edge:type', $lobby_type)
        ->setValue($task_phids),
      id(new PhabricatorSelectEditField())
        ->setKey('progress')
        ->setLabel(pht('Progress'))
        ->setBulkEditLabel(pht('Set progress goals to'))
        ->setDescription(pht('Progress of the goals.'))
        ->setConduitDescription(pht('Change the progress of the goals.'))
        ->setConduitTypeDescription(pht('New goals progress statuses.'))
        ->setTransactionType(LobbyStickitProgressTransaction::TRANSACTIONTYPE)
        ->setValue($object->getProgress())
        ->setOptions($progress_map)
        ->setOptionAliases($progress_map)
        ->setCommentActionLabel(pht('Change Progress')),
      id(new PhabricatorSelectEditField())
        ->setKey('archive')
        ->setLabel(pht('Statuses'))
        ->setTransactionType(
          LobbyStickitArchiveTransaction::TRANSACTIONTYPE)
        ->setDescription(pht('Complete or Incomplete the goals.'))
        ->setConduitDescription(pht('Complete or Incomplete the goals.'))
        ->setConduitTypeDescription(pht('Complete or Incomplete the goals.'))
        ->setValue($object->getIsArchived())
        ->setOptions($options),
    );
    $conpherence_type = LobbyGoalsHasRoomEdgeType::EDGECONST;

    $src_phid = $object->getPHID();
    if ($src_phid) {
      $edge_query = id(new PhabricatorEdgeQuery())
        ->withSourcePHIDs(array($src_phid))
        ->withEdgeTypes(
          array(
            $conpherence_type,
          ));
      $edge_query->execute();

      $conpherence_phids = $edge_query->getDestinationPHIDs(
        array($src_phid),
        array($conpherence_type));
    } else {
      $conpherence_phids = array();
    }

    $fields[] = id(new PhabricatorHandlesEditField())
      ->setKey('conpherence')
      ->setLabel(pht('conpherence'))
      ->setDescription(pht('Associate conpherence lobby.'))
      ->setConduitDescription(
        pht('Associate between conpherence and maniphest'))
      ->setConduitTypeDescription(
        pht('Associate between conpherence and maniphest.'))
      ->setUseEdgeTransactions(true)
      ->setIsFormField(false)
      ->setTransactionType(PhabricatorTransactions::TYPE_EDGE)
      ->setMetadataValue('edge:type', $conpherence_type)
      ->setValue($conpherence_phids);

    return $fields;
  }

  protected function newEditResponse(
    AphrontRequest $request,
    $object,
    array $xactions) {

    $response_type = $request->getStr('responseType');

    if (substr($response_type, 0, 6) === '/lobby') {
      return id(new AphrontAjaxResponse())->setContent(
        array(
          'redirect' => $response_type.$object->getID().'/',
      ));
    }

    return parent::newEditResponse($request, $object, $xactions);
  }
}
