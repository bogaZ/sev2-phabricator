<?php

final class CoursepathItemTestOption extends CoursepathDAO {

  protected $testID;
  protected $name;

  protected function getConfiguration() {
    return array(
      self::CONFIG_COLUMN_SCHEMA => array(
        'name' => 'text?',
      ),
      self::CONFIG_KEY_SCHEMA => array(
        'testID' => array(
          'columns' => array('testID'),
        ),
      ),
    ) + parent::getConfiguration();
  }

}
