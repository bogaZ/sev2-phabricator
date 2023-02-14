create database if not exists {$NAMESPACE}_almanac;
create database if not exists {$NAMESPACE}_application;
create database if not exists {$NAMESPACE}_audit;
create database if not exists {$NAMESPACE}_auth;
create database if not exists {$NAMESPACE}_badges;
create database if not exists {$NAMESPACE}_cache;
create database if not exists {$NAMESPACE}_calendar;
create database if not exists {$NAMESPACE}_chatlog;
create database if not exists {$NAMESPACE}_conduit;
create database if not exists {$NAMESPACE}_config;
create database if not exists {$NAMESPACE}_conpherence;
create database if not exists {$NAMESPACE}_countdown;
create database if not exists {$NAMESPACE}_coursepath;
create database if not exists {$NAMESPACE}_daemon;
create database if not exists {$NAMESPACE}_dashboard;
create database if not exists {$NAMESPACE}_differential;
create database if not exists {$NAMESPACE}_diviner;
create database if not exists {$NAMESPACE}_doorkeeper;
create database if not exists {$NAMESPACE}_draft;
create database if not exists {$NAMESPACE}_drydock;
create database if not exists {$NAMESPACE}_fact;
create database if not exists {$NAMESPACE}_feed;
create database if not exists {$NAMESPACE}_file;
create database if not exists {$NAMESPACE}_flag;
create database if not exists {$NAMESPACE}_fund;
create database if not exists {$NAMESPACE}_harbormaster;
create database if not exists {$NAMESPACE}_herald;
create database if not exists {$NAMESPACE}_job;
create database if not exists {$NAMESPACE}_legalpad;
create database if not exists {$NAMESPACE}_lobby;
create database if not exists {$NAMESPACE}_maniphest;
create database if not exists {$NAMESPACE}_mention;
create database if not exists {$NAMESPACE}_metamta;
create database if not exists {$NAMESPACE}_mood;
create database if not exists {$NAMESPACE}_multimeter;
create database if not exists {$NAMESPACE}_nuance;
create database if not exists {$NAMESPACE}_oauth_server;
create database if not exists {$NAMESPACE}_owners;
create database if not exists {$NAMESPACE}_packages;
create database if not exists {$NAMESPACE}_passphrase;
create database if not exists {$NAMESPACE}_paste;
create database if not exists {$NAMESPACE}_performance;
create database if not exists {$NAMESPACE}_phame;
create database if not exists {$NAMESPACE}_phlux;
create database if not exists {$NAMESPACE}_pholio;
create database if not exists {$NAMESPACE}_phortune;
create database if not exists {$NAMESPACE}_phragment;
create database if not exists {$NAMESPACE}_phrequent;
create database if not exists {$NAMESPACE}_phriction;
create database if not exists {$NAMESPACE}_phurl;
create database if not exists {$NAMESPACE}_policy;
create database if not exists {$NAMESPACE}_ponder;
create database if not exists {$NAMESPACE}_project;
create database if not exists {$NAMESPACE}_releeph;
create database if not exists {$NAMESPACE}_repository;
create database if not exists {$NAMESPACE}_search;
create database if not exists {$NAMESPACE}_slowvote;
create database if not exists {$NAMESPACE}_spaces;
create database if not exists {$NAMESPACE}_suite;
create database if not exists {$NAMESPACE}_system;
create database if not exists {$NAMESPACE}_token;
create database if not exists {$NAMESPACE}_user;
create database if not exists {$NAMESPACE}_worker;
create database if not exists {$NAMESPACE}_xhpast;
create database if not exists {$NAMESPACE}_xhprof;

-- insert into `{$WORKSPACE}_patch_status` (patch, applied) values ("{$NAMESPACE}:quickstart.sql", 1556231689);