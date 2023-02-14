<?php

final class DiffusionRepositoryBuildInfoEditor
  extends PhabricatorApplicationTransactionEditor {

  public function getEditorObjectsDescription() {
    return pht('Repository Build Info');
  }

  public function getCreateObjectTitle($author, $object) {
    return pht('%s created this build info.', $author);
  }

  public function getCreateObjectTitleForFeed($author, $object) {
    return pht('%s created %s.', $author, $object);
  }

  protected function supportsSearch() {
    return true;
  }

  public function getTransactionTypes() {
    $types = parent::getTransactionTypes();
    $types[] = PhabricatorTransactions::TYPE_COMMENT;
    $types[] = PhabricatorTransactions::TYPE_EDGE;
    $types[] = PhabricatorTransactions::TYPE_EDIT_POLICY;

    return $types;
  }

  public function getEditorApplicationClass() {
    return 'PhabricatorDiffusionApplication';
  }

}
