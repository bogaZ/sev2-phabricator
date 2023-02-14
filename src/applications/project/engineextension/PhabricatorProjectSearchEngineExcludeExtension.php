<?php

final class PhabricatorProjectSearchEngineExcludeExtension
  extends PhabricatorSearchEngineExtension {

  const EXTENSIONKEY = 'excludedProjects';

  public function isExtensionEnabled() {
    return PhabricatorApplication::isClassInstalled(
      'PhabricatorProjectApplication');
  }

  public function getExtensionName() {
    return pht('Support for Projects');
  }

  public function getExtensionOrder() {
    return 3000;
  }

  public function supportsObject($object) {
    return ($object instanceof PhabricatorProjectInterface);
  }

  public function applyConstraintsToQuery(
    $object,
    $query,
    PhabricatorSavedQuery $saved,
    array $map) {
    if (!empty($map['excludedProjectPHIDs'])) {
      if (is_string($map['excludedProjectPHIDs'][0])) {
        $map['excludedProjectPHIDs'] =
          array_unique($map['excludedProjectPHIDs']);
        foreach ($map['excludedProjectPHIDs'] as $key => $value) {
          $map['excludedProjectPHIDs'][$key] =
            $this->generateQueryConstraintNot($value);
        }
      }
        $query->withEdgeLogicConstraints(
          PhabricatorProjectObjectHasProjectEdgeType::EDGECONST,
          $map['excludedProjectPHIDs']);
    }
  }

  public function generateQueryConstraintNot($phids) {
    $val = new PhabricatorQueryConstraint(
      PhabricatorQueryConstraint::OPERATOR_NOT,
      $phids);

    return($val);
  }

  public function getSearchFields($object) {
    $fields = array();

    $fields[] = id(new PhabricatorProjectSearchField())
      ->setKey('excludedProjectPHIDs')
      ->setConduitKey('excludedProjects')
      ->setLabel(pht('Excluded Tags'))
      ->setEdgeType('excluded')
      ->setDescription(
        pht('Search for objects tagged with given projects.'));
    return $fields;
  }

  public function getSearchAttachments($object) {
    return array(
      id(new PhabricatorProjectsSearchEngineAttachment())
        ->setAttachmentKey('excludedProjects'),
    );
  }


}
