<?php

final class PerformanceManageCapability
  extends PhabricatorPolicyCapability {

  const CAPABILITY = 'performance.manage';

  public function getCapabilityName() {
    return pht('Manage KPI Policy');
  }

  public function describeCapabilityRejection() {
    return pht(
      'You do not have permission to manage KPI.');
  }

}
