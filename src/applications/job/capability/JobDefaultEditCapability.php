<?php

final class JobDefaultEditCapability
  extends PhabricatorPolicyCapability {

  const CAPABILITY = 'job.default.edit';

  public function getCapabilityName() {
    return pht('Default Job Posting Edit Policy');
  }

}
