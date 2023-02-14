USE {$NAMESPACE}_maniphest;

ALTER TABLE `{$WORKSPACE}_maniphest_task` 
ADD `ownerQAPHID` varbinary(64) DEFAULT NULL,
ADD `pointsQA` double DEFAULT NULL,
ADD INDEX (`ownerQAPHID`);
