use {$NAMESPACE}_user;

ALTER TABLE `{$WORKSPACE}_user` 
ADD `jid` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL;
