<?php

final class PhabricatorSuiteCapabilityManageBilling
  extends PhabricatorPolicyCapability {

  const CAPABILITY = 'suite.manage.billing';

  public function getCapabilityName() {
    return pht('Manage Billing Policy');
  }

  public function describeCapabilityRejection() {
    return pht(
      'You do not have permission to manage billing.');
  }

}
