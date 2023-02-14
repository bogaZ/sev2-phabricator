<?php

final class Sev2RolesConfigType
  extends PhabricatorJSONConfigType {

  const TYPEKEY = 'sev2.roles';

  public function validateStoredValue(
    PhabricatorConfigOption $option,
    $value) {

    Sev2Roles::validateConfiguration($value);
  }

}
