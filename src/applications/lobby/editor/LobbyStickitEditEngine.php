<?php

final class LobbyStickitEditEngine
  extends PhabricatorEditEngine {

  const ENGINECONST = 'lobby.stickit';

  public function getEngineName() {
    return pht('Lobby Stickit');
  }

  public function getEngineApplicationClass() {
    return 'PhabricatorLobbyApplication';
  }

  public function getSummaryHeader() {
    return pht('Configure Stickit Forms');
  }

  public function getSummaryText() {
    return pht('Configure creation and editing forms in Stickit.');
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
    return pht('Add Stickit');
  }

  protected function getObjectEditTitleText($object) {
    return pht('Edit Stickit: %s', $object->getTitle());
  }

  protected function getObjectEditShortText($object) {
    return $object->getTitle();
  }

  protected function getObjectCreateShortText() {
    return pht('Add Stickit');
  }

  protected function getObjectName() {
    return pht('Lobby Stickit');
  }

  protected function getObjectCreateCancelURI($object) {
    return $this->getApplication()->getApplicationURI('stickit/');
  }

  protected function getEditorURI() {
    return $this->getApplication()->getApplicationURI('stickit/edit/');
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
      LobbyManageCapability::CAPABILITY);
  }

  protected function buildCustomEditFields($object) {

    $fields = array(
      id(new PhabricatorSelectEditField())
        ->setKey('noteType')
        ->setLabel(pht('Type'))
        ->setBulkEditLabel(pht('Set type to'))
        ->setDescription(pht('Type of the stickit.'))
        ->setConduitDescription(pht('Change the stickit type.'))
        ->setConduitTypeDescription(pht('New stickit type.'))
        ->setTransactionType(LobbyStickitNoteTypeTransaction::TRANSACTIONTYPE)
        ->setIsCopyable(true)
        ->setValue($object->getNoteType())
        ->setOptions(LobbyStickit::getTypeMap())
        ->setCommentActionLabel(pht('Change Type'))
        ->setCommentActionValue(LobbyStickit::TYPE_MEMO),
      id(new PhabricatorTextEditField())
        ->setKey('title')
        ->setLabel(pht('Title'))
        ->setDescription(pht('Title.'))
        ->setConduitTypeDescription(pht('New stickit title.'))
        ->setValue($object->getTitle())
        ->setIsRequired(true)
        ->setTransactionType(
          LobbyStickitTitleTransaction::TRANSACTIONTYPE),
      id(new PhabricatorRemarkupEditField())
        ->setKey('content')
        ->setLabel(pht('Content'))
        ->setDescription(pht('The content of the stickit.'))
        ->setConduitTypeDescription(pht('New stickit content.'))
        ->setValue($object->getContent())
        ->setTransactionType(
          LobbyStickitContentTransaction::TRANSACTIONTYPE),
    );

    $conpherence_type = LobbyStickitHasRoomEdgeType::EDGECONST;

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
      ->setConduitDescription(pht('Associate between conpherence and maniphest'))
      ->setConduitTypeDescription(pht('Associate between conpherence and maniphest.'))
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

    if (substr( $response_type, 0, 6 ) === "/lobby") {
      return id(new AphrontAjaxResponse())->setContent(
        array(
          'redirect' => $response_type.$object->getID().'/'
      ));
    }

    return parent::newEditResponse($request, $object, $xactions);
  }
}
