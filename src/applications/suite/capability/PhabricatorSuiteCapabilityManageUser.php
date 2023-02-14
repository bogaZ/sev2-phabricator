<?php

final class PhabricatorSuiteCapabilityManageUser
  extends PhabricatorPolicyCapability {

  const CAPABILITY = 'suite.manage.user';

  public function getCapabilityName() {
    return pht('Manage User Policy');
  }

  public function describeCapabilityRejection() {
    return pht(
      'You do not have permission to manage user.');
  }

}
