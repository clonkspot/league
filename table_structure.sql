-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `lg_admin_permissions`
--

CREATE TABLE IF NOT EXISTS `lg_admin_permissions` (
  `user_id` int(10) unsigned NOT NULL,
  `part` varchar(45) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `method` varchar(45) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`user_id`,`part`,`method`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `lg_clans`
--

CREATE TABLE IF NOT EXISTS `lg_clans` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `founder_user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(45) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `link` varchar(100) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `tag` varchar(5) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `password` varchar(32) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `date_created` int(10) unsigned NOT NULL DEFAULT '0',
  `join_disabled` enum('Y','N') COLLATE latin1_general_ci NOT NULL DEFAULT 'N',
  `description` text COLLATE latin1_general_ci NOT NULL,
  `cronjob_update_stats` tinyint(1) NOT NULL COMMENT 'if flag is set: update stats in cronjob',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `lg_clan_scores`
--

CREATE TABLE IF NOT EXISTS `lg_clan_scores` (
  `clan_id` int(10) unsigned NOT NULL DEFAULT '0',
  `league_id` int(10) unsigned NOT NULL DEFAULT '0',
  `score` int(11) NOT NULL DEFAULT '0',
  `rank` int(10) unsigned NOT NULL DEFAULT '0',
  `trend` enum('up','down','none') COLLATE latin1_general_ci NOT NULL DEFAULT 'none',
  `date_last_game` int(10) unsigned NOT NULL DEFAULT '0',
  `games_count` int(10) unsigned NOT NULL DEFAULT '0',
  `favorite_scenario_id` int(10) unsigned NOT NULL DEFAULT '0',
  `duration` int(10) unsigned NOT NULL DEFAULT '0',
  `rank_order` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`clan_id`,`league_id`),
  KEY `league_id_rank_order` (`league_id`,`rank_order`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `lg_cuid_bans`
--

CREATE TABLE IF NOT EXISTS `lg_cuid_bans` (
  `cuid` varchar(8) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `date_created` int(10) NOT NULL DEFAULT '0',
  `date_until` int(10) NOT NULL DEFAULT '0',
  `reason` text COLLATE latin1_general_ci NOT NULL,
  `comment` text COLLATE latin1_general_ci NOT NULL,
  `is_league_only` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`cuid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `lg_debug_counter`
--

CREATE TABLE IF NOT EXISTS `lg_debug_counter` (
  `name` varchar(45) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `value` int(11) NOT NULL DEFAULT '0',
  `start` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_update` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `mean_duration` double NOT NULL,
  `revision` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`name`,`revision`) USING BTREE,
  KEY `revision` (`revision`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `lg_games`
--

CREATE TABLE IF NOT EXISTS `lg_games` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date_created` int(10) unsigned NOT NULL DEFAULT '0',
  `date_last_update` int(10) unsigned NOT NULL DEFAULT '0',
  `csid` varchar(32) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `type` enum('melee','settle','noleague') COLLATE latin1_general_ci NOT NULL DEFAULT 'noleague',
  `status` enum('created','lobby','running','ended') COLLATE latin1_general_ci NOT NULL DEFAULT 'created',
  `date_ended` int(10) unsigned NOT NULL DEFAULT '0',
  `scenario_id` int(10) unsigned NOT NULL DEFAULT '0',
  `product_id` int(10) unsigned NOT NULL DEFAULT '0',
  `scenario_title` varchar(45) COLLATE latin1_general_ci NOT NULL DEFAULT '' COMMENT 'just for noleague-games',
  `is_password_needed` tinyint(1) NOT NULL DEFAULT '0',
  `is_fair_crew_strength` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_join_allowed` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `date_started` int(10) unsigned NOT NULL DEFAULT '0',
  `duration` int(10) unsigned NOT NULL DEFAULT '0',
  `host_ip` varchar(15) COLLATE latin1_general_ci NOT NULL,
  `is_randominv_teamdistribution` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `icon_number` int(10) unsigned DEFAULT NULL,
  `is_revoked` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_official_server` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_paused` tinyint(1) NOT NULL DEFAULT '0',
  `frame` int(10) unsigned NOT NULL DEFAULT '0',
  `seed` int(10) unsigned NOT NULL DEFAULT '0',
  `settle_score` decimal(10,0) unsigned NOT NULL DEFAULT '0',
  `settle_rank` int(10) unsigned NOT NULL DEFAULT '0',
  `record_status` enum('none','incomplete','complete') COLLATE latin1_general_ci NOT NULL DEFAULT 'none',
  `record_filename` varchar(45) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `csid` (`csid`),
  KEY `type` (`type`),
  KEY `status` (`status`),
  KEY `date_created` (`date_created`),
  KEY `date_last_update` (`date_last_update`),
  KEY `frame` (`frame`),
  KEY `scenario_id` (`scenario_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `lg_game_leagues`
--

CREATE TABLE IF NOT EXISTS `lg_game_leagues` (
  `game_id` int(10) unsigned NOT NULL DEFAULT '0',
  `league_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`game_id`,`league_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `lg_game_list_html`
--

CREATE TABLE IF NOT EXISTS `lg_game_list_html` (
  `game_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `game_list_html` text COLLATE latin1_general_ci NOT NULL,
  `language_id` int(10) unsigned NOT NULL DEFAULT '0',
  `game_list_html_2` text COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`game_id`,`language_id`),
  KEY `language_id` (`language_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `lg_game_players`
--

CREATE TABLE IF NOT EXISTS `lg_game_players` (
  `game_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `status` enum('auth','active','joined','won','lost','disconnected','quit') COLLATE latin1_general_ci NOT NULL DEFAULT 'auth',
  `auid` varchar(32) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `date_auth` int(10) unsigned NOT NULL DEFAULT '0',
  `team_id` int(10) unsigned DEFAULT NULL,
  `player_id` int(10) unsigned DEFAULT NULL,
  `color` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(45) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `fbid` varchar(32) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `client_id` int(10) unsigned NOT NULL DEFAULT '0',
  `is_disconnected` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `ip` varchar(15) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `user_is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  UNIQUE KEY `game_player` (`game_id`,`player_id`),
  KEY `team_id` (`team_id`),
  KEY `game_user` (`game_id`,`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `lg_game_reference`
--

CREATE TABLE IF NOT EXISTS `lg_game_reference` (
  `game_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `reference` text COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`game_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `lg_game_reference_cache`
--

CREATE TABLE IF NOT EXISTS `lg_game_reference_cache` (
  `game_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `reference_ini` text COLLATE latin1_general_ci NOT NULL,
  `date_created` int(10) unsigned NOT NULL DEFAULT '0',
  `product_string` varchar(2) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`game_id`),
  KEY `date_created` (`date_created`),
  KEY `product_string` (`product_string`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `lg_game_scores`
--

CREATE TABLE IF NOT EXISTS `lg_game_scores` (
  `league_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(10) unsigned NOT NULL DEFAULT '0',
  `player_id` int(10) unsigned NOT NULL DEFAULT '0',
  `score` decimal(10,0) NOT NULL DEFAULT '0',
  `old_player_score` decimal(10,0) NOT NULL DEFAULT '0',
  `settle_rank` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`league_id`,`player_id`,`game_id`),
  KEY `game_id` (`game_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `lg_game_teams`
--

CREATE TABLE IF NOT EXISTS `lg_game_teams` (
  `team_id` int(10) unsigned NOT NULL DEFAULT '0',
  `game_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(45) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `color` int(10) unsigned NOT NULL DEFAULT '0',
  `team_status` enum('active','won','lost') COLLATE latin1_general_ci NOT NULL DEFAULT 'active',
  PRIMARY KEY (`team_id`,`game_id`),
  KEY `game_id` (`game_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `lg_languages`
--

CREATE TABLE IF NOT EXISTS `lg_languages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(3) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `name` varchar(45) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `flag` varchar(255) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `lg_leagues`
--

CREATE TABLE IF NOT EXISTS `lg_leagues` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name_sid` int(10) unsigned NOT NULL DEFAULT '0',
  `description_sid` int(10) unsigned NOT NULL DEFAULT '0',
  `type` enum('melee','settle') COLLATE latin1_general_ci NOT NULL DEFAULT 'melee',
  `date_start` int(10) unsigned NOT NULL DEFAULT '0',
  `date_end` int(10) unsigned NOT NULL DEFAULT '0',
  `icon` varchar(255) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `trophies` varchar(255) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `recurrent` enum('Y','N') COLLATE latin1_general_ci NOT NULL DEFAULT 'N',
  `scenario_restriction` enum('Y','N') COLLATE latin1_general_ci NOT NULL DEFAULT 'Y',
  `ranking_timeout` int(10) unsigned NOT NULL DEFAULT '0',
  `product_id` int(10) unsigned NOT NULL DEFAULT '0',
  `filter_icon_on` varchar(255) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `filter_icon_off` varchar(255) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `priority` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `lg_league_scenarios`
--

CREATE TABLE IF NOT EXISTS `lg_league_scenarios` (
  `league_id` int(10) unsigned NOT NULL DEFAULT '0',
  `scenario_id` int(10) unsigned NOT NULL DEFAULT '0',
  `max_player_count` int(10) unsigned NOT NULL,
  PRIMARY KEY (`league_id`,`scenario_id`),
  KEY `scenario_id` (`scenario_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `lg_log`
--

CREATE TABLE IF NOT EXISTS `lg_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` int(10) unsigned NOT NULL DEFAULT '0',
  `type` enum('info','error','game_info','user_error','auth_join','game_start') COLLATE latin1_general_ci NOT NULL DEFAULT 'info',
  `string` text COLLATE latin1_general_ci NOT NULL,
  `csid` varchar(32) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `date` (`date`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `lg_news_statistics`
--

CREATE TABLE IF NOT EXISTS `lg_news_statistics` (
  `k` varchar(45) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `v` text COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`k`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `lg_products`
--

CREATE TABLE IF NOT EXISTS `lg_products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `icon` varchar(255) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `version` varchar(45) COLLATE latin1_general_ci NOT NULL,
  `product_string` varchar(2) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_string` (`product_string`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `lg_rank_symbols`
--

CREATE TABLE IF NOT EXISTS `lg_rank_symbols` (
  `rank_number` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `icon` varchar(255) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `rank_min` int(10) unsigned DEFAULT NULL,
  `rank_max` int(10) unsigned DEFAULT NULL,
  `score_min` int(10) unsigned DEFAULT NULL,
  `score_max` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`rank_number`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `lg_resources`
--

CREATE TABLE IF NOT EXISTS `lg_resources` (
  `filename` varchar(255) NOT NULL DEFAULT '',
  `hash` varchar(40) NOT NULL DEFAULT '',
  PRIMARY KEY (`filename`) USING BTREE,
  KEY `hash` (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `lg_scenarios`
--

CREATE TABLE IF NOT EXISTS `lg_scenarios` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `games_count` int(10) unsigned NOT NULL DEFAULT '0',
  `name_sid` int(10) unsigned NOT NULL DEFAULT '0',
  `active` enum('Y','N') COLLATE latin1_general_ci NOT NULL DEFAULT 'Y',
  `type` enum('melee','team_melee','settle') COLLATE latin1_general_ci NOT NULL DEFAULT 'melee',
  `autocreated` enum('Y','N') COLLATE latin1_general_ci NOT NULL DEFAULT 'N',
  `product_id` int(10) unsigned NOT NULL DEFAULT '0',
  `icon_number` int(10) unsigned DEFAULT NULL,
  `settle_base_score` int(10) unsigned NOT NULL DEFAULT '0',
  `settle_time_bonus_score` int(10) unsigned NOT NULL DEFAULT '0',
  `duration` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `games_count` (`games_count`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `lg_scenario_versions`
--

CREATE TABLE IF NOT EXISTS `lg_scenario_versions` (
  `hash` varchar(32) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `author` varchar(45) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `filename` varchar(255) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `date_created` int(10) unsigned NOT NULL DEFAULT '0',
  `comment` varchar(255) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `scenario_id` int(10) unsigned NOT NULL DEFAULT '0',
  `hash_sha` varchar(40) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`hash`,`scenario_id`,`author`,`filename`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `lg_scores`
--

CREATE TABLE IF NOT EXISTS `lg_scores` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `league_id` int(10) unsigned NOT NULL DEFAULT '0',
  `score` int(10) unsigned NOT NULL DEFAULT '0',
  `rank` int(10) unsigned NOT NULL DEFAULT '0',
  `trend` enum('up','down','none') COLLATE latin1_general_ci NOT NULL DEFAULT 'none',
  `date_last_game` int(10) unsigned NOT NULL DEFAULT '0',
  `games_won` int(10) unsigned NOT NULL DEFAULT '0',
  `games_lost` int(10) unsigned NOT NULL DEFAULT '0',
  `favorite_scenario_id` int(10) unsigned NOT NULL DEFAULT '0',
  `date_last_inactivity_malus` int(10) unsigned NOT NULL DEFAULT '0',
  `duration` int(10) unsigned NOT NULL DEFAULT '0',
  `rank_order` int(10) unsigned NOT NULL DEFAULT '0',
  `user_is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`,`league_id`),
  KEY `league_id_rank_order` (`league_id`,`rank_order`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `lg_strings`
--

CREATE TABLE IF NOT EXISTS `lg_strings` (
  `id` int(10) unsigned NOT NULL DEFAULT '0',
  `string` text COLLATE latin1_general_ci NOT NULL,
  `language_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`,`language_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `lg_users`
--

CREATE TABLE IF NOT EXISTS `lg_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `password` varchar(32) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `real_name` varchar(45) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `date_created` int(10) unsigned NOT NULL DEFAULT '0',
  `date_last_login` int(10) unsigned NOT NULL DEFAULT '0',
  `date_last_game` int(10) unsigned NOT NULL DEFAULT '0',
  `email` varchar(45) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `picture` varchar(255) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `games_melee_won` int(10) unsigned NOT NULL DEFAULT '0',
  `games_melee_lost` int(10) unsigned NOT NULL DEFAULT '0',
  `games_melee_disconnected` int(10) unsigned NOT NULL DEFAULT '0',
  `games_settle_won` int(10) unsigned NOT NULL DEFAULT '0',
  `games_settle_lost` int(10) unsigned NOT NULL DEFAULT '0',
  `admin` tinyint(1) NOT NULL DEFAULT '0',
  `cuid` varchar(8) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `clan_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `clan_id` (`clan_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
