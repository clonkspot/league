<?php

include_once('league.class.php');
include_once('scenario.class.php');

// Settlement league with custom scoring
// * Ignores the time bonus and retrieves scores from scenario scripts
// * Scores are performances per player. Thus, there is no score per game (lg_games.settle_score, settle_rank, no_settle_rank unused)
// * Contrary to custom score melee leagues, scores are still per scenario and the total score is the sum
// * The latest score of a player counts. This may also go down, which is intended behaviour. If scenario authors don't want this, they should use custom scenario progress data to prevent it
class league_settle_custom extends league_settle
{

	function evaluate(&$game, &$scenario)
	{
		// fill in player performance: If not set by scenario but players have won, assume they got the maximum score
		$log = new log();
		$log->add('custom settle league evaluation of game '.$game->data['id']);
		$players = $game->get_players();
		foreach($players AS $player)
		{
			if ($player->is_winner() && (!isset($player->player_data['performance']) || $player->player_data['performance']==0))
			{
				$player->player_data['performance'] = $scenario->data['settle_base_score'];
				$player->save_player_data();
			}
		}
		// do evaluation
		return league_settle::evaluate($game, $scenario);
	}

	function is_player_winner(&$player)
	{
		// winner-check: Having a performance is a win.
		return ($player->player_data['performance'] > 0);
	}
	
	//recalculate all settle-scores for this scenario:
	function calculate_scores(&$game, &$scenario)
	{
		global $database;
		
		// Store performances as scores
		$players = $game->get_players();
		foreach($players AS $player)
		{
			$player_score_data = array();
			$player_score_data['game_id'] = $game->data['id'];
			$player_score_data['player_id'] = $player->player_data['player_id'];
			$player_score_data['league_id'] = $this->data['id'];
			$player_score_data['old_player_score'] = 0; // how to get? it's somewhere in the reference...
			$player_score_data['score'] = $player->player_data['performance'];
			$player_score_data['settle_rank'] = 0; // not known yet
			$database->insert_update('lg_game_scores', $player_score_data);
		}
		// Update all scores of played scenario
		$this->recalculate_scenario_ranks($scenario->data['id']);
		
		// Now update total score of all involved players
		foreach($players AS $player) $this->recalculate_user_score($player->data['id']);
	}
	
	// recalculate all ranks in a scenario
	function recalculate_scenario_ranks($scenario_id)
	{
		global $database;
		$log = new log();
		$log->add('recalc scenario scores for '.$scenario_id);
		// Clear previous ranks of this scenario
		$database->query("UPDATE lg_game_scores AS gs
			JOIN lg_games as g ON gs.game_id = g.id
			SET gs.settle_rank = 0
			WHERE gs.league_id = '".$this->data['id']."'
				AND g.scenario_id = '".$scenario_id."'");
		// Get all last games of players in this league (could also do MAX(gs.score) to get the game with the highest score here)
		// Note that MAX(gs.game_id) is the last game started, but might not be the last game ended if people play multiple games simultaneously. Well, whatever...
		$all_score_data = $database->get_array("SELECT gs.player_id, gs.score, MAX(gs.game_id)
				FROM lg_game_scores AS gs
				LEFT JOIN lg_games AS g ON g.id = gs.game_id
				LEFT JOIN lg_game_players AS gp ON gp.game_id = gs.game_id AND gs.player_id = gp.player_id
				LEFT JOIN lg_game_leagues AS gl ON gl.game_id = gs.game_id AND gl.league_id = '".$this->data['id']."'
				WHERE g.scenario_id = '".$scenario_id."'
				AND g.status='ended' AND g.is_revoked=0
				GROUP BY gp.user_id
				ORDER BY gs.score DESC");
		//$log = new log();
		//$log->add("all_score_data=".print_r($all_score_data,TRUE));
		// Now assign ranks
		// If multiple users have the same score, they will get the same rank for this scenario
		$rank = 1; $n_same_rank = 0;
		$last_score = $all_score_data[0]['score'];
		foreach ($all_score_data as $score_data)
		{
			$score = $score_data['score'];
			if ($score < $last_score)
			{
				$rank = $rank + $n_same_rank;
				$last_score = $score;
				$n_same_rank = 1;
			}
			else
			{
				$n_same_rank = $n_same_rank + 1;
			}
			// Assign new settle rank to this score (effectively marking it as the game used for calculating score totals)
			$database->query("UPDATE lg_game_scores AS gs
				SET gs.settle_rank = '".$rank."'
				WHERE gs.league_id = '".$this->data['id']."'
				AND gs.game_id = '".$score_data['MAX(gs.game_id)']."'
				AND gs.player_id = '".$database->escape($score_data['player_id'])."'");
		}
		return TRUE;
	}
	
	// update total score of given user by summing score of all scenarios in this league
	function recalculate_user_score($user_id)
	{
		global $database;
		$database->query("INSERT INTO lg_scores
			(user_id, league_id, score)
		VALUES
			('".$user_id."', '".$this->data['id']."', 
				(SELECT SUM(score) FROM lg_game_scores AS gs
				LEFT JOIN lg_game_players AS gp ON gp.game_id = gs.game_id AND gs.player_id = gp.player_id
				WHERE gp.user_id = '".$user_id."'
				AND gs.league_id = '".$this->data['id']."'
				AND gs.settle_rank != 0) )
		ON DUPLICATE KEY UPDATE
			score = VALUES(score)");
		return TRUE;
	}
	
	//not for regular use
	function recalculate_all_scores()
	{
		global $database;
		$log = new log();
		$log->add('recalc all scores in league '.$this->data['id']);
		// iterate over all scenarios in the league and recalculate their ranks
		$all_scenarios = $database->get_array("SELECT DISTINCT scenario_id FROM lg_league_scenarios WHERE league_id = '".$this->data['id']."'");
		foreach ($all_scenarios as $scenario) $this->recalculate_scenario_ranks($scenario['scenario_id']);
		// now iterate over all users and recalculate their total score
		$all_users = $database->get_array("SELECT DISTINCT gp.user_id
			FROM lg_game_scores AS gs
				LEFT JOIN lg_game_players AS gp
					ON gs.player_id = gp.player_id
				WHERE gs.settle_rank != 0
					AND gs.league_id = '".$this->data['id']."'");
		foreach ($all_users as $user) $this->recalculate_user_score($user['user_id']);
		return TRUE;
	}
	
	//get the score a player would win, if his team wins
	function get_winner_score_and_simulate_evaluation(&$game, $winner_player_id)
	{
		// TODO: Subtract current player score as base
		return $scenario->data['settle_base_score'];
	}
	
	
	function calculate_trends()
	{
		//TODO
	}
	
	//calculate trends for all melee leagues. to be done by cronjob.
	function calculate_clan_trends()
	{
		//TODO
	}
	
}


?>