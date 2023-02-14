<?php

final class PolicyListConduitAPIMethod
  extends PolicyConduitAPIMethod {

  public function getAPIMethodName() {
    return 'policy.list';
  }

  public function getMethodDescription() {
    return pht('Get List Policies.');
  }

  protected function defineParamTypes() {
    return array();
  }

  protected function defineReturnType() {
    return 'nonempty dict';
  }

  protected function execute(ConduitAPIRequest $request) {
    $viewer = $request->getViewer();

    $basic_policy_array = array(
      'Public' => 'public',
      'All Users' => 'users',
      'Administrators' => 'admin',
      'No-one' => 'no-one'
    );
    $basic_policies = array();
    $basic_policy = array();

    foreach ($basic_policy_array as $key => $value) {
      $basic_policy['title'] = $key;
      $basic_policy['value'] = $value;

      $basic_policies[] = $basic_policy;
    }

    $object_policy_array = array(
      'Subscribers' => 'obj.subscriptions.subscribers',
      'Task Author' => 'obj.maniphest.author'
    );
    $object_policies = array();
    $object_policy = array();

    foreach ($object_policy_array as $key => $value) {
      $object_policy['title'] = $key;
      $object_policy['value'] = $value;

      $object_policies[] = $object_policy;
    }

    $user_policy_array = array(
      $viewer->getFullName() => $viewer->getPHID(),
    );
    $user_policies = array();
    $user_policy = array();

    foreach ($user_policy_array as $key => $value) {
      $user_policy['title'] = $key;
      $user_policy['value'] = $value;

      $user_policies[] = $user_policy;
    }

    return array(
      'basicPolicies' => $basic_policies,
      'objectPolicies' => $object_policies,
      'userPolicies' => $user_policies,
    );
  }
}