/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.6.2-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: clnk_league
-- ------------------------------------------------------
-- Server version	11.6.2-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `lg_admin_permissions`
--

DROP TABLE IF EXISTS `lg_admin_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lg_admin_permissions` (
  `user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `part` varchar(45) NOT NULL DEFAULT '',
  `method` varchar(45) NOT NULL DEFAULT '',
  PRIMARY KEY (`user_id`,`part`,`method`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lg_clan_scores`
--

DROP TABLE IF EXISTS `lg_clan_scores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lg_clan_scores` (
  `clan_id` int(10) unsigned NOT NULL DEFAULT 0,
  `league_id` int(10) unsigned NOT NULL DEFAULT 0,
  `score` int(11) NOT NULL DEFAULT 0,
  `rank` int(10) unsigned NOT NULL DEFAULT 0,
  `trend` enum('up','down','none') NOT NULL DEFAULT 'none',
  `date_last_game` int(10) unsigned NOT NULL DEFAULT 0,
  `games_count` int(10) unsigned NOT NULL DEFAULT 0,
  `favorite_scenario_id` int(10) unsigned NOT NULL DEFAULT 0,
  `duration` int(10) unsigned NOT NULL DEFAULT 0,
  `rank_order` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`clan_id`,`league_id`),
  KEY `league_id_rank_order` (`league_id`,`rank_order`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lg_clans`
--

DROP TABLE IF EXISTS `lg_clans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lg_clans` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `founder_user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `name` varchar(45) NOT NULL DEFAULT '',
  `link` varchar(100) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `tag` varchar(5) NOT NULL DEFAULT '',
  `password` varchar(255) NOT NULL,
  `date_created` int(10) unsigned NOT NULL DEFAULT 0,
  `join_disabled` enum('Y','N') NOT NULL DEFAULT 'N',
  `description` text CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `cronjob_update_stats` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'if flag is set: update stats in cronjob',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lg_cuid_bans`
--

DROP TABLE IF EXISTS `lg_cuid_bans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lg_cuid_bans` (
  `cuid` varchar(45) NOT NULL DEFAULT '',
  `date_created` int(10) NOT NULL DEFAULT 0,
  `date_until` int(10) NOT NULL DEFAULT 0,
  `reason` text NOT NULL,
  `comment` text NOT NULL,
  `is_league_only` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`cuid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lg_debug_counter`
--

DROP TABLE IF EXISTS `lg_debug_counter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lg_debug_counter` (
  `name` varchar(45) NOT NULL DEFAULT '',
  `value` int(10) unsigned NOT NULL DEFAULT 0,
  `start` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `mean_duration` double NOT NULL DEFAULT 0,
  `revision` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`name`,`revision`),
  KEY `revision` (`revision`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lg_game_leagues`
--

DROP TABLE IF EXISTS `lg_game_leagues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lg_game_leagues` (
  `game_id` int(10) unsigned NOT NULL DEFAULT 0,
  `league_id` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`game_id`,`league_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lg_game_list_html`
--

DROP TABLE IF EXISTS `lg_game_list_html`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lg_game_list_html` (
  `game_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `game_list_html` text NOT NULL,
  `language_id` int(10) unsigned NOT NULL DEFAULT 0,
  `game_list_html_2` text NOT NULL,
  PRIMARY KEY (`game_id`,`language_id`),
  KEY `language_id` (`language_id`)
) ENGINE=InnoDB AUTO_INCREMENT=35977 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lg_game_players`
--

DROP TABLE IF EXISTS `lg_game_players`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lg_game_players` (
  `game_id` int(10) unsigned NOT NULL DEFAULT 0,
  `user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `status` enum('auth','active','joined','won','lost','disconnected','quit') NOT NULL DEFAULT 'auth',
  `auid` varchar(32) DEFAULT NULL,
  `date_auth` int(11) NOT NULL DEFAULT 0,
  `team_id` int(10) unsigned DEFAULT NULL,
  `player_id` int(10) unsigned DEFAULT NULL,
  `color` int(10) unsigned NOT NULL DEFAULT 0,
  `name` varchar(45) NOT NULL DEFAULT '',
  `fbid` varchar(32) DEFAULT NULL,
  `client_id` int(10) unsigned NOT NULL DEFAULT 0,
  `is_disconnected` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `ip` varchar(45) NOT NULL,
  `user_is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `performance` decimal(10,0) NOT NULL DEFAULT 0,
  `client_cuid` varchar(8) NOT NULL,
  UNIQUE KEY `game_player` (`game_id`,`player_id`),
  KEY `team_id` (`team_id`),
  KEY `game_user` (`game_id`,`user_id`),
  KEY `auid` (`auid`),
  KEY `fbid` (`fbid`),
  KEY `user_id` (`user_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lg_game_reference`
--

DROP TABLE IF EXISTS `lg_game_reference`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lg_game_reference` (
  `game_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `reference` text NOT NULL,
  PRIMARY KEY (`game_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1389194 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lg_game_reference_cache`
--

DROP TABLE IF EXISTS `lg_game_reference_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lg_game_reference_cache` (
  `game_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `reference_ini` text NOT NULL,
  `date_created` int(10) unsigned NOT NULL DEFAULT 0,
  `product_string` char(2) NOT NULL DEFAULT '',
  PRIMARY KEY (`game_id`),
  KEY `date_created` (`date_created`),
  KEY `product_string` (`product_string`)
) ENGINE=InnoDB AUTO_INCREMENT=1389194 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lg_game_scores`
--

DROP TABLE IF EXISTS `lg_game_scores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lg_game_scores` (
  `league_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(10) unsigned NOT NULL DEFAULT 0,
  `player_id` int(10) unsigned NOT NULL DEFAULT 0,
  `score` decimal(10,0) NOT NULL DEFAULT 0,
  `old_player_score` decimal(10,0) NOT NULL DEFAULT 0,
  `settle_rank` int(10) unsigned NOT NULL DEFAULT 0,
  `bonus` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`league_id`,`player_id`,`game_id`),
  KEY `game_id` (`game_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lg_game_teams`
--

DROP TABLE IF EXISTS `lg_game_teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lg_game_teams` (
  `team_id` int(10) unsigned NOT NULL DEFAULT 0,
  `game_id` int(10) unsigned NOT NULL DEFAULT 0,
  `name` varchar(45) NOT NULL DEFAULT '',
  `color` int(10) unsigned NOT NULL DEFAULT 0,
  `team_status` enum('active','won','lost') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`team_id`,`game_id`),
  KEY `game_id` (`game_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lg_games`
--

DROP TABLE IF EXISTS `lg_games`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lg_games` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date_created` int(10) unsigned NOT NULL DEFAULT 0,
  `date_created_neg` int(10) NOT NULL DEFAULT 0 COMMENT 'should always be -date_created',
  `date_last_update` int(10) unsigned NOT NULL DEFAULT 0,
  `csid` varchar(32) DEFAULT NULL,
  `type` enum('melee','settle','noleague') NOT NULL DEFAULT 'noleague',
  `status` enum('created','lobby','running','ended') NOT NULL DEFAULT 'created',
  `date_ended` int(10) unsigned NOT NULL DEFAULT 0,
  `scenario_id` int(10) unsigned NOT NULL DEFAULT 0,
  `product_id` int(10) unsigned NOT NULL DEFAULT 0,
  `scenario_title` varchar(255) NOT NULL,
  `is_password_needed` tinyint(1) NOT NULL DEFAULT 0,
  `is_fair_crew_strength` tinyint(1) NOT NULL DEFAULT 0,
  `is_join_allowed` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `date_started` int(10) unsigned NOT NULL DEFAULT 0,
  `duration` int(10) unsigned NOT NULL DEFAULT 0,
  `host_ip` varchar(45) NOT NULL DEFAULT '',
  `is_randominv_teamdistribution` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `icon_number` int(10) unsigned DEFAULT NULL,
  `is_revoked` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `is_official_server` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `is_paused` tinyint(1) NOT NULL DEFAULT 0,
  `frame` int(10) unsigned NOT NULL DEFAULT 0,
  `seed` int(10) unsigned NOT NULL DEFAULT 0,
  `settle_score` decimal(10,0) unsigned NOT NULL DEFAULT 0,
  `settle_rank` int(10) unsigned NOT NULL DEFAULT 0,
  `no_settle_rank` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'should exactly be 1 if settle_rank==0',
  `record_status` enum('none','incomplete','complete') NOT NULL DEFAULT 'none',
  `record_filename` varchar(45) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `date_created` (`date_created`),
  KEY `date_last_update` (`date_last_update`),
  KEY `status` (`status`),
  KEY `frame` (`frame`),
  KEY `settle_score` (`settle_score`,`date_ended`),
  KEY `scenario_id` (`scenario_id`,`no_settle_rank`,`settle_rank`,`date_created_neg`),
  KEY `host_ip` (`host_ip`,`date_created`),
  KEY `csid` (`csid`)
) ENGINE=InnoDB AUTO_INCREMENT=1389194 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lg_languages`
--

DROP TABLE IF EXISTS `lg_languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lg_languages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` char(3) NOT NULL DEFAULT '',
  `name` varchar(45) NOT NULL DEFAULT '',
  `flag` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lg_languages`
--

LOCK TABLES `lg_languages` WRITE;
/*!40000 ALTER TABLE `lg_languages` DISABLE KEYS */;
INSERT INTO `lg_languages` VALUES
(1,'en','English','images/icons/flag_en.gif'),
(2,'de','Deutsch','images/icons/flag_de.gif');
/*!40000 ALTER TABLE `lg_languages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lg_league_scenarios`
--

DROP TABLE IF EXISTS `lg_league_scenarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lg_league_scenarios` (
  `league_id` int(10) unsigned NOT NULL DEFAULT 0,
  `scenario_id` int(10) unsigned NOT NULL DEFAULT 0,
  `max_player_count` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`league_id`,`scenario_id`),
  KEY `scenario_id` (`scenario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lg_leagues`
--

DROP TABLE IF EXISTS `lg_leagues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lg_leagues` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name_sid` int(10) unsigned NOT NULL DEFAULT 0,
  `description_sid` int(10) unsigned NOT NULL DEFAULT 0,
  `type` enum('melee','settle') NOT NULL DEFAULT 'melee',
  `date_start` int(10) unsigned NOT NULL DEFAULT 0,
  `date_end` int(10) unsigned NOT NULL DEFAULT 0,
  `icon` varchar(255) NOT NULL DEFAULT '',
  `trophies` varchar(255) NOT NULL DEFAULT '',
  `recurrent` enum('Y','N') NOT NULL DEFAULT 'N',
  `scenario_restriction` enum('Y','N') NOT NULL DEFAULT 'Y',
  `ranking_timeout` int(10) unsigned NOT NULL DEFAULT 0,
  `product_id` int(10) unsigned NOT NULL DEFAULT 0,
  `filter_icon_on` varchar(255) NOT NULL DEFAULT '',
  `filter_icon_off` varchar(255) NOT NULL DEFAULT '',
  `priority` int(10) unsigned NOT NULL DEFAULT 0,
  `score_decay` int(5) unsigned NOT NULL DEFAULT 0,
  `date_last_decay` int(10) unsigned NOT NULL DEFAULT 0,
  `custom_scoring` enum('Y','N') NOT NULL DEFAULT 'N',
  `bonus_max` int(10) unsigned NOT NULL DEFAULT 0,
  `bonus_account_max` int(10) unsigned NOT NULL DEFAULT 0,
  `stream_retain_time` int(5) unsigned NOT NULL DEFAULT 31 COMMENT 'in days',
  `decay_interval` int(11) unsigned NOT NULL DEFAULT 561600 COMMENT 'interval in seconds after which score decay is applied',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lg_leagues`
--

LOCK TABLES `lg_leagues` WRITE;
/*!40000 ALTER TABLE `lg_leagues` DISABLE KEYS */;
INSERT INTO `lg_leagues` VALUES
(1,1,2,'melee',1388530800,1893456000,'images/icons/league_melee_16.gif','','N','N',0,1,'images/icons/type_melee.gif','images/icons/type_melee_off.gif',0,10,1740718354,'N',20,100,31,561600),
(2,114,115,'settle',1401649200,1893456000,'images/icons/league_clonkmars_16.png','','N','Y',0,1,'images/icons/type_mars.png','images/icons/type_mars_off.png',0,0,0,'N',50,0,31,561600),
(4,2086,2087,'melee',1457893800,1460498400,'league_seasonal_16.png','','N','Y',0,1,'league_seasonal.png','league_seasonal_off.png',2,5,1460428304,'N',40,120,31,43200),
(5,2274,2275,'settle',1478559600,1893456000,'images/icons/league_settlement_16.gif','','N','Y',0,1,'images/icons/league_settlement.gif','images/icons/league_settlement_off.gif',0,0,0,'N',100,0,31,561600),
(6,2931,2932,'melee',1564610400,1596232800,'images/icons/league_micromelee_16.gif','','N','Y',0,1,'images/icons/league_micromelee.gif','images/icons/league_micromelee_off.gif',2,0,0,'N',40,120,31,561600);
/*!40000 ALTER TABLE `lg_leagues` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lg_log`
--

DROP TABLE IF EXISTS `lg_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lg_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` int(10) unsigned NOT NULL DEFAULT 0,
  `type` enum('info','error','game_info','user_error','auth_join','game_start') NOT NULL DEFAULT 'info',
  `string` text NOT NULL,
  `csid` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `date` (`date`)
) ENGINE=InnoDB AUTO_INCREMENT=3905850 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lg_news_statistics`
--

DROP TABLE IF EXISTS `lg_news_statistics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lg_news_statistics` (
  `k` varchar(45) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `v` text CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lg_products`
--

DROP TABLE IF EXISTS `lg_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lg_products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL DEFAULT '',
  `icon` varchar(255) NOT NULL DEFAULT '',
  `version` varchar(45) NOT NULL DEFAULT '',
  `product_string` char(2) NOT NULL DEFAULT '',
  `motd_sid` int(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_string` (`product_string`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lg_products`
--

LOCK TABLES `lg_products` WRITE;
/*!40000 ALTER TABLE `lg_products` DISABLE KEYS */;
INSERT INTO `lg_products` VALUES
(1,'Clonk Rage','cr.png','4,9,10,6,328','CR',323);
/*!40000 ALTER TABLE `lg_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lg_rank_symbols`
--

DROP TABLE IF EXISTS `lg_rank_symbols`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lg_rank_symbols` (
  `rank_number` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `icon` varchar(255) NOT NULL DEFAULT '',
  `rank_min` int(10) unsigned DEFAULT NULL,
  `rank_max` int(10) unsigned DEFAULT NULL,
  `score_min` int(10) unsigned DEFAULT NULL,
  `score_max` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`rank_number`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lg_rank_symbols`
--

LOCK TABLES `lg_rank_symbols` WRITE;
/*!40000 ALTER TABLE `lg_rank_symbols` DISABLE KEYS */;
INSERT INTO `lg_rank_symbols` VALUES
(1,'images/icons/rank_1.png',1,1,NULL,NULL),
(2,'images/icons/rank_2.png',2,2,NULL,NULL),
(3,'images/icons/rank_3.png',3,3,NULL,NULL),
(4,'images/icons/rank_4.png',4,6,NULL,NULL),
(5,'images/icons/rank_5.png',7,10,NULL,NULL),
(6,'images/icons/rank_6.png',11,20,NULL,NULL),
(7,'images/icons/rank_7.png',NULL,NULL,300,999999),
(8,'images/icons/rank_8.png',NULL,NULL,200,299),
(9,'images/icons/rank_9.png',NULL,NULL,100,199);
/*!40000 ALTER TABLE `lg_rank_symbols` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lg_resources`
--

DROP TABLE IF EXISTS `lg_resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lg_resources` (
  `filename` varchar(255) NOT NULL DEFAULT '',
  `hash` varchar(40) NOT NULL DEFAULT '',
  PRIMARY KEY (`filename`),
  KEY `hash` (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lg_scenario_user_data`
--

DROP TABLE IF EXISTS `lg_scenario_user_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lg_scenario_user_data` (
  `scenario_id` int(10) unsigned NOT NULL DEFAULT 0,
  `user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `data` varchar(2048) NOT NULL DEFAULT '',
  PRIMARY KEY (`scenario_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lg_scenario_versions`
--

DROP TABLE IF EXISTS `lg_scenario_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lg_scenario_versions` (
  `hash` varchar(32) NOT NULL DEFAULT '',
  `author` varchar(45) NOT NULL DEFAULT '',
  `filename` varchar(255) NOT NULL DEFAULT '',
  `date_created` int(10) unsigned NOT NULL DEFAULT 0,
  `comment` varchar(255) NOT NULL DEFAULT '',
  `scenario_id` int(10) unsigned NOT NULL DEFAULT 0,
  `hash_sha` varchar(40) NOT NULL DEFAULT '',
  PRIMARY KEY (`hash`,`scenario_id`,`author`,`filename`,`hash_sha`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lg_scenarios`
--

DROP TABLE IF EXISTS `lg_scenarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lg_scenarios` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `games_count` int(10) unsigned NOT NULL DEFAULT 0,
  `name_sid` int(10) unsigned NOT NULL DEFAULT 0,
  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `type` enum('melee','team_melee','settle') NOT NULL DEFAULT 'melee',
  `autocreated` enum('Y','N') NOT NULL DEFAULT 'N',
  `product_id` int(10) unsigned NOT NULL DEFAULT 0,
  `icon_number` int(10) unsigned DEFAULT NULL,
  `settle_base_score` int(10) unsigned NOT NULL DEFAULT 0,
  `settle_time_bonus_score` int(10) unsigned NOT NULL DEFAULT 0,
  `duration` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `games_count` (`games_count`)
) ENGINE=InnoDB AUTO_INCREMENT=3306 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lg_scores`
--

DROP TABLE IF EXISTS `lg_scores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lg_scores` (
  `user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `league_id` int(10) unsigned NOT NULL DEFAULT 0,
  `score` int(10) unsigned NOT NULL DEFAULT 0,
  `rank` int(10) unsigned NOT NULL DEFAULT 0,
  `trend` enum('up','down','none') NOT NULL DEFAULT 'none',
  `date_last_game` int(10) unsigned NOT NULL DEFAULT 0,
  `games_won` int(10) unsigned NOT NULL DEFAULT 0,
  `games_lost` int(10) unsigned NOT NULL DEFAULT 0,
  `favorite_scenario_id` int(10) unsigned NOT NULL DEFAULT 0,
  `date_last_inactivity_malus` int(10) unsigned NOT NULL DEFAULT 0,
  `duration` int(10) unsigned NOT NULL DEFAULT 0,
  `rank_order` int(10) unsigned NOT NULL DEFAULT 0,
  `user_is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `bonus_account` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`,`league_id`),
  KEY `leauge_id_rank_order` (`league_id`,`rank_order`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lg_strings`
--

DROP TABLE IF EXISTS `lg_strings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lg_strings` (
  `id` int(10) unsigned NOT NULL DEFAULT 0,
  `string` text NOT NULL,
  `language_id` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`,`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lg_users`
--

DROP TABLE IF EXISTS `lg_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lg_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL DEFAULT '',
  `password` varchar(32) NOT NULL DEFAULT '',
  `real_name` varchar(45) NOT NULL DEFAULT '',
  `date_created` int(10) unsigned NOT NULL DEFAULT 0,
  `date_last_login` int(10) unsigned NOT NULL DEFAULT 0,
  `date_last_game` int(10) unsigned NOT NULL DEFAULT 0,
  `email` varchar(45) NOT NULL DEFAULT '',
  `picture` varchar(255) NOT NULL DEFAULT '',
  `games_melee_won` int(10) unsigned NOT NULL DEFAULT 0,
  `games_melee_lost` int(10) unsigned NOT NULL DEFAULT 0,
  `games_melee_disconnected` int(10) unsigned NOT NULL DEFAULT 0,
  `games_settle_won` int(10) unsigned NOT NULL DEFAULT 0,
  `games_settle_lost` int(10) unsigned NOT NULL DEFAULT 0,
  `admin` tinyint(1) NOT NULL DEFAULT 0,
  `operator` varchar(20) NOT NULL DEFAULT '',
  `cuid` varchar(45) NOT NULL DEFAULT '',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `clan_id` int(10) unsigned DEFAULT NULL,
  `old_names` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `clan_id` (`clan_id`)
) ENGINE=InnoDB AUTO_INCREMENT=629 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `phinxlog`
--

DROP TABLE IF EXISTS `phinxlog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phinxlog` (
  `version` bigint(20) NOT NULL,
  `migration_name` varchar(100) DEFAULT NULL,
  `start_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `end_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `breakpoint` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phinxlog`
--

LOCK TABLES `phinxlog` WRITE;
/*!40000 ALTER TABLE `phinxlog` DISABLE KEYS */;
INSERT INTO `phinxlog` VALUES
(20160323223502,'DecayInterval','2016-03-23 23:24:09','2016-03-23 23:24:09',0),
(20190905200652,'FixShortColumns','2019-09-05 20:31:40','2019-09-05 20:32:05',0);
/*!40000 ALTER TABLE `phinxlog` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2025-03-01 18:02:06
