<?php


require_once('lib/database.class.php');
$profiling_start_time = microtime_float();

require_once('config.php');
require_once('lib/log.class.php');
require_once('lib/game.class.php');

include_once('lib/debug_counter.class.php');

$debug_sql_slow_log = FALSE; //cronjob can be slow?

$log = new log();

if(!($_REQUEST['password'] == '' || !isset($_SERVER['REMOTE_USER'])))
{
	$log->add("daily cronjob: no access for ".$_SERVER["REMOTE_ADDR"]);
	exit;
}

$log->add("started daily cronjob (from ".$_SERVER["REMOTE_ADDR"].") - delete old log entries, apply inactivity malus, calculate trends, calculate favorite scenarios, restart recurrenct leagues, delete old noleague-games, delete small clans, delete game-list-html-cache");


//remove old log-entries:
$log->delete_old_entries();

//remove old game-references:
$game = new game();
$game->delete_old_references();
$game->delete_old_ids();
$game->delete_old_record_streams();

//apply inacitivty malus to all melee-leagues:
include_once('lib/league.class.php');
include_once('lib/league_melee.class.php');
$league_melee = new league_melee();
$league_melee->apply_inactivity_malus();

//recalculate trends after inactivity-mali for all melee-leagues:
//and calc many other stuff for all leagues:
$a = $database->get_array("SELECT id FROM lg_leagues");
foreach($a AS $league_id)
{
	$league_melee->load_data($league_id['id']);
	$league_melee->calculate_ranks();
	$league_melee->calculate_clan_ranks();
	
	//calculate favorite-scenarios
	$b = $database->get_array("SELECT user_id FROM lg_scores 
		WHERE league_id = '".$league_id['id']."'
		AND date_last_game > '".(time()-60*60*24)."'");	
	if(is_array($b))
	{
		foreach($b AS $user_id)
		{
			$league_melee->calculate_favorite_scenario($user_id['user_id']);
		}
	}
	
	
	//calculate clan stats for all clans which are marked for update:
	$b = $database->get_array("SELECT id FROM lg_clans 
		WHERE cronjob_update_stats = 1");
	if(is_array($b))
	{
		foreach($b AS $clan)
		{
			$league_melee->calculate_clan_stats($clan['id']);
		}
	}	
	
	//calculate clan-favorite-scenarios
	$b = $database->get_array("SELECT clan_id FROM lg_clan_scores 
		WHERE league_id = '".$league_id['id']."'
		AND date_last_game > '".(time()-60*60*24)."'");
	if(is_array($b))
	{
		foreach($b AS $clan_id)
		{
			$league_melee->calculate_clan_favorite_scenario($clan_id['clan_id']);
		}
	}
	

}

//calculate trends for all melee-leagues:
$league_melee->calculate_trends();
$league_melee->calculate_clan_trends();

//restart all recurrent leagues:
$league = new league();
$league->restart_recurrent_leagues();

//delete old no-league-games:
require_once('lib/game.class.php');
$game = new game();
$game->delete_old_noleague_games();


//delete game-list-html-cache
$database->query("TRUNCATE lg_game_list_html");


//delete clans < 3 members:
require_once('lib/clan.class.php');
$clan = new clan();
$clan->delete_small_clans();


//optimize some table where large deletions could have occured (cache tables)
$database->query("OPTIMIZE TABLE lg_game_list_html, lg_game_reference_cache");

//large table, needed to do this every day?:
//$database->query("OPTIMIZE TABLE lg_game_reference");


//update debug-counter:
$duration = microtime_float() - $profiling_start_time;
$debug_counter = new debug_counter();
$debug_counter->increment('cronjob_daily',$duration);	

?>
