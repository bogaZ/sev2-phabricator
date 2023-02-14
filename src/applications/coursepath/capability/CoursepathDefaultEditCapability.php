<?php

final class CoursepathDefaultEditCapability
  extends PhabricatorPolicyCapability {

  const CAPABILITY = 'coursepath.path.default.edit';

  public function getCapabilityName() {
    return pht('Default Course Path Edit Policy');
  }

}
