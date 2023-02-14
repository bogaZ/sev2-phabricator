use {$NAMESPACE}_lobby;

ALTER TABLE `{$WORKSPACE}_lobby_stickit` 
ADD  `progress` tinyint UNSIGNED NOT NULL DEFAULT 0;
