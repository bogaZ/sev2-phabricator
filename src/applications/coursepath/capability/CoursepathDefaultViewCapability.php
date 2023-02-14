<?php

final class CoursepathDefaultViewCapability
  extends PhabricatorPolicyCapability {

  const CAPABILITY = 'coursepath.path.default.view';

  public function getCapabilityName() {
    return pht('Default Course Path View Policy');
  }

  public function shouldAllowPublicPolicySetting() {
    return true;
  }

}
