use {$NAMESPACE}_fact;

CREATE TABLE IF NOT EXISTS `{$WORKSPACE}_fact_aggregate` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `factType` varchar(32) COLLATE utf8mb4_bin NOT NULL,
  `objectPHID` varbinary(64) NOT NULL,
  `valueX` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `factType` (`factType`,`objectPHID`)
) ;



CREATE TABLE IF NOT EXISTS `{$WORKSPACE}_fact_chart` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `chartKey` binary(12) NOT NULL,
  `chartParameters` longtext COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_chart` (`chartKey`)
) ;



CREATE TABLE IF NOT EXISTS `{$WORKSPACE}_fact_cursor` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) COLLATE utf8mb4_bin NOT NULL,
  `position` varchar(64) COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ;



CREATE TABLE IF NOT EXISTS `{$WORKSPACE}_fact_intdatapoint` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `keyID` int(10) unsigned NOT NULL,
  `objectID` int(10) unsigned NOT NULL,
  `dimensionID` int(10) unsigned DEFAULT NULL,
  `value` bigint(20) NOT NULL,
  `epoch` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `key_dimension` (`keyID`,`dimensionID`),
  KEY `key_object` (`objectID`)
) ;



CREATE TABLE IF NOT EXISTS `{$WORKSPACE}_fact_keydimension` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `factKey` varchar(64) COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_factkey` (`factKey`)
) ;



CREATE TABLE IF NOT EXISTS `{$WORKSPACE}_fact_objectdimension` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `objectPHID` varbinary(64) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_object` (`objectPHID`)
) ;



CREATE TABLE IF NOT EXISTS `{$WORKSPACE}_fact_raw` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `factType` varchar(32) COLLATE utf8mb4_bin NOT NULL,
  `objectPHID` varbinary(64) NOT NULL,
  `objectA` varbinary(64) NOT NULL,
  `valueX` bigint(20) NOT NULL,
  `valueY` bigint(20) NOT NULL,
  `epoch` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `objectPHID` (`objectPHID`),
  KEY `factType` (`factType`,`epoch`),
  KEY `factType_2` (`factType`,`objectA`)
) ;
