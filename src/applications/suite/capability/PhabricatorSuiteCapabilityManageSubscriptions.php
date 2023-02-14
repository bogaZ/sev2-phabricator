<?php

final class PhabricatorSuiteCapabilityManageSubscriptions
  extends PhabricatorPolicyCapability {

  const CAPABILITY = 'suite.manage.subscriptions';

  public function getCapabilityName() {
    return pht('Manage Subscription Policy');
  }

  public function describeCapabilityRejection() {
    return pht(
      'You do not have permission to manage subscriptions.');
  }
}
