<?php

final class JobPostingFulltextEngine
  extends PhabricatorFulltextEngine {

  protected function buildAbstractDocument(
    PhabricatorSearchAbstractDocument $document,
    $object) {

    $posting = $object;

    $document->setDocumentTitle($posting->getName());

    $document->addField(
      PhabricatorSearchDocumentFieldType::FIELD_BODY,
      $posting->getDescription());

    $document->addRelationship(
      PhabricatorSearchRelationship::RELATIONSHIP_AUTHOR,
      $posting->getCreatorPHID(),
      PhabricatorPeopleUserPHIDType::TYPECONST,
      $posting->getDateCreated());

    $document->addRelationship(
      PhabricatorSearchRelationship::RELATIONSHIP_OWNER,
      $posting->getCreatorPHID(),
      PhabricatorPeopleUserPHIDType::TYPECONST,
      $posting->getDateCreated());

    $document->addRelationship(
      $posting->getIsCancelled()
        ? PhabricatorSearchRelationship::RELATIONSHIP_CLOSED
        : PhabricatorSearchRelationship::RELATIONSHIP_OPEN,
      $posting->getPHID(),
      JobPostingPHIDType::TYPECONST,
      PhabricatorTime::getNow());
  }

}
