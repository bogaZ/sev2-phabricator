<?php

final class PhabricatorProjectNewMilestoneTransaction
  extends PhabricatorProjectTypeTransaction {

  const TRANSACTIONTYPE = 'project:milestone.conduit';

  public function generateOldValue($object) {
    return null;
  }

  public function applyInternalEffects($object, $value) {
    $parent_phid = $value;
    $project = id(new PhabricatorProjectQuery())
      ->setViewer($this->getActor())
      ->withPHIDs(array($parent_phid))
      ->requireCapabilities(
        array(
          PhabricatorPolicyCapability::CAN_VIEW,
          PhabricatorPolicyCapability::CAN_EDIT,
        ))
      ->executeOne();

    $object->attachParentProject($project);

    $number = $project->loadNextMilestoneNumber();

    $object->setMilestoneNumber($number);
    $object->setParentProjectPHID($value);
  }

}
