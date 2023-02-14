<?php

final class JobDefaultViewCapability
  extends PhabricatorPolicyCapability {

  const CAPABILITY = 'job.default.view';

  public function getCapabilityName() {
    return pht('Default Job Posting View Policy');
  }

  public function shouldAllowPublicPolicySetting() {
    return true;
  }

}
