<?php

final class SuiteProfileConfigOptions
  extends PhabricatorApplicationConfigOptions {

  public function getName() {
    return pht('Suite Profiles');
  }

  public function getDescription() {
    return pht('Suite profiles configuration.');
  }

  public function getIcon() {
    return 'fa-bolt';
  }

  public function getGroup() {
    return 'apps';
  }

  public function getOptions() {

    $default = array(
      id(new SuiteProfileIsRspField())->getFieldKey() => true,
      id(new SuiteProfileUpForField())->getFieldKey() => true,
      id(new SuiteProfileGraduationTargetMonthField())->getFieldKey() => true,
    );

    foreach ($default as $key => $enabled) {
      $default[$key] = array(
        'disabled' => !$enabled,
      );
    }

    $custom_field_type = 'custom:PhabricatorCustomFieldConfigOptionType';

    return array(
      // SEV-2
      $this->newOption('sev2.workspace', 'string', 'refactory')
        ->setLocked(true)
        ->setDescription(
          pht('SEV-2 workspace.'))
        ->addExample(pht('admin@workspace.com'), pht('Your workspace email')),
      $this->newOption('sev2.admin-email', 'string', 'admin@refactory.id')
        ->setLocked(true)
        ->setDescription(
          pht('SEV-2 workspace owner email.'))
        ->addExample(pht('admin@workspace.com'), pht('Your workspace email')),
      $this->newOption('sev2.oauth', 'set', '{}')
        ->setHidden(true)
        ->setDescription(
          pht('SEV-2 oauth configuration')),
      $this->newOption('suite-profile.fields', $custom_field_type, $default)
        ->setCustomData(id(new SuiteProfile())->getCustomFieldBaseClass())
        ->setDescription(pht('Select and reorder suite profile fields.')),
    );
  }

}
