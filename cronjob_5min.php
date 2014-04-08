<?php

require_once('lib/database.class.php');
$profiling_start_time = microtime_float();

require_once('config.php');
require_once('lib/log.class.php');
require_once('lib/language.class.php');

include_once('lib/debug_counter.class.php');

$debug_sql_slow_log = FALSE; //cronjob can be slow?

//require_once('lib/Debug.class.php');//DEBUG

$log = new log();
$language = new language();

if(!($_REQUEST['password'] == $cronjob_password || !isset($_SERVER['REMOTE_USER'])))
{
	$log->add("daily cronjob: no access for ".$_SERVER["REMOTE_ADDR"]);
	exit;
}

//$log->add("started 5min cronjob (from ".$_SERVER["REMOTE_ADDR"].") - delete timeout games, delete timeout auth-players");


require_once('lib/game.class.php');

$game = new game();
//delete all old games with no update:
$game->delete_timeout_games();

//delete all old auth-players that did not join a game
$game->delete_timeout_auth_players();


require_once('lib/news_statistics.class.php');
$news_statistics = new news_statistics();
$news_statistics->gather_statistics();

//update debug-counter:
$duration = microtime_float() - $profiling_start_time;
$debug_counter = new debug_counter();
$debug_counter->increment('cronjob_5min',$duration);	

		//show_vars();//DEBUG
		//$database->display_debug_sql();	//DEBUG


?>