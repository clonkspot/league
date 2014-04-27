--

ALTER TABLE `lg_users` CHANGE `password` `password` VARCHAR( 100 ) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL DEFAULT '';
ALTER TABLE `lg_clans` CHANGE `password` `password` VARCHAR( 100 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '';

-- 

ALTER TABLE  `lg_users` DROP COLUMN `date_last_rename`;

-- Rev 1573 (Adventure league)

CREATE TABLE `lg_scenario_user_data` (
  `scenario_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `data` varchar(2048) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`scenario_id`,`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Rev 1471 (Players can rename themselves)

ALTER TABLE  `lg_users` ADD  `date_last_rename` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0' AFTER  `date_last_game` ;

-- Rev 1472 (Some SQL optimizations concerning game list)

ALTER TABLE  `lg_games` ADD  `date_created_neg` INT( 10 ) NOT NULL DEFAULT  '0' COMMENT  'should always be -date_created' AFTER  `date_created` ;
ALTER TABLE  `lg_games` ADD  `no_settle_rank` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT  '0' COMMENT  'should exactly be 1 if settle_rank==0' AFTER  `settle_rank` ;
UPDATE `lg_games` SET date_created_neg = -date_created;
UPDATE `lg_games` SET no_settle_rank = 1 WHERE settle_rank = 0;

ALTER TABLE  `lg_games` ADD INDEX (  `settle_score` ,  `date_ended` );

ALTER TABLE  `lg_games` DROP INDEX  `scenario_id`,
ADD INDEX  `scenario_id` (  `scenario_id` ,  `no_settle_rank` ,  `settle_rank` ,  `date_created_neg` );

ALTER TABLE  `lg_games` ADD INDEX (  `host_ip` ,  `date_created` );

-- Rev 1479 (Periodically clear old CSIDs, AUIDs and FBIDs)

ALTER TABLE  `lg_games` CHANGE  `csid`  `csid` VARCHAR( 32 ) CHARACTER SET latin1 COLLATE latin1_general_ci NULL;
ALTER TABLE  `lg_game_players` CHANGE  `auid`  `auid` VARCHAR( 32 ) CHARACTER SET latin1 COLLATE latin1_general_ci NULL;
ALTER TABLE  `lg_game_players` CHANGE  `fbid`  `fbid` VARCHAR( 32 ) CHARACTER SET latin1 COLLATE latin1_general_ci NULL;

-- Rev 1480 (Operators)

ALTER TABLE  `lg_users` ADD  `operator` VARCHAR( 20 ) NOT NULL AFTER  `admin` ;

-- Rev 1490 (MotD support)

ALTER TABLE  `lg_products` ADD  `motd` TEXT NOT NULL ;

-- Rev 1494 (MotD localization)

ALTER TABLE  `lg_products` CHANGE  `motd`  `motd_sid` INT( 10 ) NOT NULL;

-- Rev 1514 (Support for custom scoring by script)

ALTER TABLE  `lg_leagues` ADD  `custom_scoring` ENUM(  'Y',  'N' ) NOT NULL DEFAULT  'N';
ALTER TABLE  `lg_game_players` ADD  `performance` DECIMAL( 10, 0 ) NOT NULL;

-- Rev 1540 (Replaced inactivity malus by score decay)

ALTER TABLE  `lg_leagues` ADD  `score_decay` INT( 5 ) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE  `lg_leagues` ADD  `date_last_decay` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0';

ALTER TABLE  `lg_scores` DROP  `date_last_inactivity_malus`;

-- Rev 1556 (Inactivity decay can be earned back as bonus points)

ALTER TABLE  `lg_game_scores` ADD  `bonus` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0';
ALTER TABLE  `lg_scores` ADD  `bonus_account` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0';
ALTER TABLE  `lg_leagues` ADD  `bonus_max` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0';
ALTER TABLE  `lg_leagues` ADD  `bonus_account_max` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0';

-- Rev 1559 (Delete record streams after some time (default 31 days))

ALTER TABLE  `lg_leagues` ADD  `stream_retain_time` INT( 5 ) UNSIGNED NOT NULL DEFAULT  '31' COMMENT  'in days';

-- Rev 1563 (Save back old names of users on rename)

ALTER TABLE  `lg_users` ADD  `old_names` VARCHAR( 255 ) NOT NULL;

-- Rev 1565 (Save CUIDs of the clients players used)

ALTER TABLE  `lg_game_players` ADD  `client_cuid` VARCHAR( 8 ) NOT NULL;
