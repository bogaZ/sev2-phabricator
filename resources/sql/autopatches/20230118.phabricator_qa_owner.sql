USE {$NAMESPACE}_maniphest;

ALTER TABLE `{$WORKSPACE}_maniphest_task` 
ADD `ownerQAOrdering` varchar(64) DEFAULT NULL;