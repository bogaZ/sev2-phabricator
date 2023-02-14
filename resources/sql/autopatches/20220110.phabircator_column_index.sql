
use {$NAMESPACE}_project;

ALTER TABLE `{$WORKSPACE}_project_column` 
ADD `columnType` int(10) unsigned DEFAULT NULL,
ADD INDEX (`columnType`);