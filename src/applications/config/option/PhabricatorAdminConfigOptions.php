<?php

final class PhabricatorAdminConfigOptions
  extends PhabricatorApplicationConfigOptions {

  public function getName() {
    return pht('Admin');
  }

  public function getDescription() {
    return pht('Initial Admin Configuration.');
  }

  public function getIcon() {
    return 'fa-user';
  }

  public function getGroup() {
    return 'core';
  }

  public function getOptions() {
    return array(
      $this->newOption('admin.username', 'string', 'admin')
        ->setLocked(true)
        ->setDescription(
          pht('Admin Username.')),
      $this->newOption('admin.email', 'string', 'admin@admin.id')
        ->setLocked(true)
        ->setDescription(
          pht('Email Username.')),
      $this->newOption('admin.password', 'string', null)
        ->setHidden(true)
        ->setDescription(
          pht('Admin Password.')),
    );
  }

}
