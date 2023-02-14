USE {$NAMESPACE}_lobby;

ALTER TABLE `{$WORKSPACE}_lobby_stickit` 
ADD   `isArchived` tinyint(1) DEFAULT NULL;
