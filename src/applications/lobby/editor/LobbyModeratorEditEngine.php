<?php

final class LobbyModeratorEditEngine
  extends PhabricatorEditEngine {

  const ENGINECONST = 'lobby.moderator';

  public function getEngineName() {
    return pht('Lobby Moderator');
  }

  public function getEngineApplicationClass() {
    return 'PhabricatorLobbyApplication';
  }

  public function getSummaryHeader() {
    return pht('Configure Moderator Forms');
  }

  public function getSummaryText() {
    return pht('Configure creation and editing forms in Moderator.');
  }

  public function isEngineConfigurable() {
    return false;
  }

  protected function newEditableObject() {
    return LobbyModerator::initializeNewItem($this->getViewer());
  }

  protected function newObjectQuery() {
    return new LobbyModeratorQuery();
  }

  protected function getObjectCreateTitleText($object) {
    return pht('Add Moderator');
  }

  protected function getObjectEditTitleText($object) {
    return pht('Edit Moderator: %s', $object->loadModerator()->getRealName());
  }

  protected function getObjectEditShortText($object) {
    return $object->loadModerator()->getRealName();
  }

  protected function getObjectCreateShortText() {
    return pht('Add Moderator');
  }

  protected function getObjectName() {
    return pht('Lobby Moderator');
  }

  protected function getObjectCreateCancelURI($object) {
    return $this->getApplication()->getApplicationURI('moderators/');
  }

  protected function getEditorURI() {
    return $this->getApplication()->getApplicationURI('moderators/edit/');
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

    return array(
      id(new PhabricatorDatasourceEditField())
        ->setKey('moderatorPHID')
        ->setLabel(pht('Moderator'))
        ->setDatasource(new PhabricatorPeopleDatasource())
        ->setSingleValue(true)
        ->setTransactionType(
          LobbyModeratorModeratorTransaction::TRANSACTIONTYPE)
        ->setCommentActionLabel(pht('Change Moderator'))
        ->setDescription(pht('Selected Moderator.'))
        ->setConduitDescription(pht('Change the moderator.'))
        ->setConduitTypeDescription(pht('New moderator.'))
        ->setValue($object->getModeratorPHID()),
      id(new PhabricatorDatasourceEditField())
        ->setKey('channelPHID')
        ->setLabel(pht('Channel'))
        ->setDatasource(new LobbyConpherenceDatasource())
        ->setSingleValue(true)
        ->setTransactionType(
          LobbyModeratorChannelTransaction::TRANSACTIONTYPE)
        ->setCommentActionLabel(pht('Change Channel'))
        ->setDescription(pht('Selected Channel.'))
        ->setConduitDescription(pht('Change the channel.'))
        ->setConduitTypeDescription(pht('New channel.'))
        ->setValue($object->getChannelPHID()),
    );
  }

}
