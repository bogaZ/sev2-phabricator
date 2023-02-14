<?php

final class SuiteNotification
  extends SuiteDAO {

  protected $balancePHID;
  protected $utcInitialEpoch;
  protected $targetPHID;
  protected $didNotifyEpoch;

  protected function getConfiguration() {
    return array(
      self::CONFIG_TIMESTAMPS => false,
      self::CONFIG_COLUMN_SCHEMA => array(
        'utcInitialEpoch' => 'epoch',
        'didNotifyEpoch' => 'epoch',
      ),
      self::CONFIG_KEY_SCHEMA => array(
        'key_notify' => array(
          'columns' => array('balancePHID', 'utcInitialEpoch', 'targetPHID'),
          'unique' => true,
        ),
      ),
    ) + parent::getConfiguration();
  }

}
