<?php

final class CoursepathManageCapability extends PhabricatorPolicyCapability {

  const CAPABILITY = 'coursepath.path.manage';

  public function getCapabilityName() {
    return pht('Can Manage coursepath');
  }

  public function describeCapabilityRejection() {
    return pht('You do not have permission to manage coursepath.');
  }

}
