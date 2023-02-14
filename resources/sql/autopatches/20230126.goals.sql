USE {$NAMESPACE}_conpherence;

ALTER TABLE `{$WORKSPACE}_conpherence_thread` 
ADD `tagsPHID` varbinary(64) DEFAULT NULL;
