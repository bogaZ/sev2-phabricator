<?php

final class PhabricatorUserConfigOptions
  extends PhabricatorApplicationConfigOptions {

  public function getName() {
    return pht('User Profiles');
  }

  public function getDescription() {
    return pht('User profiles configuration.');
  }

  public function getIcon() {
    return 'fa-users';
  }

  public function getGroup() {
    return 'apps';
  }

  public function getOptions() {

    $default = array(
      id(new PhabricatorUserRealNameField())->getFieldKey() => true,
      id(new PhabricatorUserPhoneNumberField())->getFieldKey() => true,
      id(new PhabricatorUserTitleField())->getFieldKey() => true,
      id(new PhabricatorUserIconField())->getFieldKey() => true,
      id(new PhabricatorUserSinceField())->getFieldKey() => true,
      id(new PhabricatorUserRolesField())->getFieldKey() => true,
      id(new PhabricatorUserStatusField())->getFieldKey() => true,
      id(new PhabricatorUserPhonePropertyField())->getFieldKey() => true,
      id(new PhabricatorUserBlurbField())->getFieldKey() => true,
    );

    foreach ($default as $key => $enabled) {
      $default[$key] = array(
        'disabled' => !$enabled,
      );
    }

    $roles_type = 'sev2.roles';
    $roles_defaults = array(
    'principal' => array(
        'name'  => pht('Principal'),
      ),
    'manager' => array(
        'name'  => pht('Manager'),
      ),
    'techfellow' => array(
        'name'  => pht('Tech Fellow'),
      ),
    'engineer' => array(
        'name'  => pht('Software Engineer'),
      ),
    'product-engineer' => array(
        'name'  => pht('Product Engineer'),
      ),

    );

    $custom_field_type = 'custom:PhabricatorCustomFieldConfigOptionType';


    return array(
      $this->newOption('user.fields', $custom_field_type, $default)
        ->setCustomData(id(new PhabricatorUser())->getCustomFieldBaseClass())
        ->setDescription(pht('Select and reorder user profile fields.')),
      $this->newOption('user.custom-field-definitions', 'wild', array())
        ->setDescription(pht('Add new simple fields to user profiles.')),
        $this->newOption(
          'sev2.roles', $roles_type, $roles_defaults)
          ->setSummary(pht('Configure custom roles names.'))
          ->setDescription(pht('Add roles fields to user profiles.')),
      $this->newOption('user.require-real-name', 'bool', true)
        ->setDescription(pht('Always require real name for user profiles.'))
        ->setBoolOptions(
          array(
            pht('Make real names required'),
            pht('Make real names optional'),
          )),
    );
  }

}
