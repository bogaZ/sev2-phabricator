<?php

final class Sev2Roles extends UserConstants {

  const UNKNOWN_PRIORITY_KEYWORD = '!!unknown!!';

  public static function getSev2RolesMap() {
    $map = self::getConfig();
    foreach ($map as $key => $spec) {
      $map[$key] = idx($spec, 'name', $key);
    }
    return $map;
  }


  public static function getConfig() {
    $config = PhabricatorEnv::getEnvConfig('sev2.roles');
    krsort($config);
    return $config;
  }

  public static function validateConfiguration($config) {
    if (!is_array($config)) {
      throw new Exception(
        pht(
          'Configuration is not valid. Sev2 Roles configurations '.
          'must be dictionaries.'));
    }

    $all_keywords = array();
    foreach ($config as $key => $value) {
      if (!is_array($value)) {
        throw new Exception(
          pht(
            'Value for key "%s" should be a dictionary.',
            $key));
      }

      PhutilTypeSpec::checkMap(
        $value,
        array(
          'name' => 'string',
        ));

      $keywords = array_keys($value);
      foreach ($keywords as $keyword) {

        $all_keywords[$keyword] = $value['name'];
      }
    }
  }

}
