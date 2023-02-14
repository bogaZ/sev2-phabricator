<?php

final class JobManageCapability extends PhabricatorPolicyCapability {

  const CAPABILITY = 'job.manage';

  public function getCapabilityName() {
    return pht('Can Manage Job Posting');
  }

  public function describeCapabilityRejection() {
    return pht('You do not have permission to manage job posting.');
  }

}
