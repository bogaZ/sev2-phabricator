USE {$NAMESPACE}_lobby;

ALTER TABLE `{$WORKSPACE}_lobby_stickit` 
ADD  `message` longtext COLLATE utf8mb4_bin DEFAULT NULL;
