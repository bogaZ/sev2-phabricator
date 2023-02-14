create database if not exists {$NAMESPACE}_meta_data;
use {$NAMESPACE}_meta_data;

CREATE TABLE `{$WORKSPACE}_hoststate` (
  `stateKey` varchar(128) COLLATE utf8mb4_bin NOT NULL,
  `stateValue` longtext COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`stateKey`)
);
CREATE TABLE `{$WORKSPACE}_patch_status` (
  `patch` varchar(128) COLLATE utf8mb4_bin NOT NULL,
  `applied` int(10) unsigned NOT NULL,
  `duration` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`patch`)
);
