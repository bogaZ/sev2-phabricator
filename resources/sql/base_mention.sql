USE `{$NAMESPACE}_mention`;

CREATE TABLE IF NOT EXISTS `{$WORKSPACE}_mention` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `phid` varbinary(64) NOT NULL,
  `callerPHID` varbinary(64) NOT NULL,
  `objectPHID` varbinary(64) NOT NULL,
  `message` longtext COLLATE utf8mb4_bin,
  `dateCreated` int(10) unsigned NOT NULL,
  `dateModified` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_phid` (`phid`)
  ) ;

CREATE TABLE IF NOT EXISTS `{$WORKSPACE}_mention_mentioned` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mentionID` int(10) unsigned NOT NULL,
  `userPHID` varbinary(64) NOT NULL,
  `dateCreated` int(10) unsigned NOT NULL,
  `dateModified` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
  );
