use {$NAMESPACE}_daemon;

CREATE TABLE IF NOT EXISTS `{$WORKSPACE}_daemon_locklog` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lockName` varchar(64) COLLATE utf8mb4_bin NOT NULL,
  `lockReleased` int(10) unsigned DEFAULT NULL,
  `lockParameters` longtext COLLATE utf8mb4_bin NOT NULL,
  `lockContext` longtext COLLATE utf8mb4_bin NOT NULL,
  `dateCreated` int(10) unsigned NOT NULL,
  `dateModified` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `key_lock` (`lockName`),
  KEY `key_created` (`dateCreated`)
) ;



CREATE TABLE IF NOT EXISTS `{$WORKSPACE}_daemon_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `daemon` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `host` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `pid` int(10) unsigned NOT NULL,
  `argv` longtext COLLATE utf8mb4_bin NOT NULL,
  `explicitArgv` longtext COLLATE utf8mb4_bin NOT NULL,
  `dateCreated` int(10) unsigned NOT NULL,
  `dateModified` int(10) unsigned NOT NULL,
  `status` varchar(8) COLLATE utf8mb4_bin NOT NULL,
  `runningAsUser` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL,
  `daemonID` varchar(64) COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_daemonID` (`daemonID`),
  KEY `status` (`status`),
  KEY `key_modified` (`dateModified`)
) ;



CREATE TABLE IF NOT EXISTS `{$WORKSPACE}_daemon_logevent` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `logID` int(10) unsigned NOT NULL,
  `logType` varchar(4) COLLATE utf8mb4_bin NOT NULL,
  `message` longtext COLLATE utf8mb4_bin NOT NULL,
  `epoch` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `logID` (`logID`,`epoch`),
  KEY `key_epoch` (`epoch`)
) ;
