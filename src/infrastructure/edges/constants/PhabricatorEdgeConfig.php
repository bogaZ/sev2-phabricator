<?php

final class PhabricatorEdgeConfig extends PhabricatorEdgeConstants {

  const TABLE_NAME_EDGE       = 'edge';
  const TABLE_NAME_EDGEDATA   = 'edgedata';

  /**
   * SEV-2 Additional aliasing
   */
  public static function getEdgeTableName() {
    return sev2table(self::TABLE_NAME_EDGE);
  }

  public static function getEdgeDataTableName() {
    return sev2table(self::TABLE_NAME_EDGEDATA);
  }

  public static function establishConnection($phid_type, $conn_type) {
    $map = PhabricatorPHIDType::getAllTypes();
    if (isset($map[$phid_type])) {
      $type = $map[$phid_type];
      $object = $type->newObject();
      if ($object) {
        return $object->establishConnection($conn_type);
      }
    }

    static $class_map = array(
      PhabricatorPHIDConstants::PHID_TYPE_TOBJ  => 'HarbormasterObject',
    );

    $class = idx($class_map, $phid_type);

    if (!$class) {
      throw new Exception(
        pht(
          "Edges are not available for objects of type '%s'!",
          $phid_type));
    }

    return newv($class, array())->establishConnection($conn_type);
  }

}
