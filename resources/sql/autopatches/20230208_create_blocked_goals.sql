use {$NAMESPACE}_lobby;

ALTER TABLE `{$WORKSPACE}_lobby_stickit` 
ADD  `description` longtext COLLATE utf8mb4_bin DEFAULT NULL;
