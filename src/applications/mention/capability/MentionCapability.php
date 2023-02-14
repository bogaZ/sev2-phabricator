<?php

final class MentionCapability
  extends PhabricatorPolicyCapability {

  const CAPABILITY = 'mention.view';

  public function getCapabilityName() {
    return pht('Mention View Policy');
  }

  public function describeCapabilityRejection() {
    return pht(
      'You do not have permission to view mention.');
  }

}
