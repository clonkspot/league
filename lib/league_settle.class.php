<?php

include_once('league.class.php');
include_once('scenario.class.php');

class league_settle extends league
{
	
	function evaluate(&$game, &$scenario)
	{
		//check if the league-type fits for the scenario-type:
		if($scenario->data['type'] != 'settle' && $game->data['type'] != 'settle')
		{
			//this should not happen because the game should not be in this league then (see game::create())
			$this->error = 'error_wrong_league_type';
			return FALSE;
		}
		
		if($game->data['frame']==0)
		{
			//no frames -> return FALSE;
			$this->error = 'error_no_frame_count';
			return FALSE;
		}
		
		$players = $game->get_players();
		//check if there was at least one winner (still alive)
		// players with a performance score will count as winners
		$at_least_one_winner = FALSE;
		foreach($players AS $player)
		{
			if($this->is_player_winner($player))
			{
				$at_least_one_winner = TRUE;
				break;
			}
		}
		if(FALSE == $at_least_one_winner)
		{
			$log = & new log();

			$log->add_error("settle-game: (id: ".$game->data['id'].") end: no winner -> delete game-league-data (leagues set: league-id: ".$this->data['id']." ");//- reference: ".$game->reference->get_ini());
			$this->error = 'error_no_winner';

			foreach($players AS $player)
			{
				$score = $player->get_score($this->data['id']);
				$score->data['games_lost']++;
				$player->data['games_settle_lost']++;
				$score->data['duration'] += $game->data['duration'];
				$score->save(); 
				$player->save();
			}
			
			return FALSE;
		}
		
		
		//TODO lock score-table?
		global $database;
		//$database->query("LOCK TABLE lg_scores WRITE, lg_game_scores WRITE"); //BE AWARE TO USE ONLY LOCKED TABLES 
		

		foreach($players AS $player)
		{
			$score = $player->get_score($this->data['id']);
			//$old_score = $score->data['score'];
			$score->data['date_last_game'] = time();
			$player->data['date_last_game'] = time();

			$score->data['games_won']++;
			$player->data['games_settle_won']++;
			
			$score->data['duration'] += $game->data['duration'];
			
			$score->save(); 
		}
		
		//TODO unlock score-table?
		//$database->query("UNLOCK TABLES");
	
		$this->calculate_scores($game, $scenario);
		
		//no need to lock the player-table that way.
		//because of multithreading, there could be wrong game-counts in the user-data, but ignore that for now.
		//they can be recalculated if it really gets a problem...
		$clans = array();
		foreach($players AS $player)
		{
			$player->save();
			
			if($player->data['clan_id'] != 0 && false == in_array($player->data['clan_id'],$clans))
			{
				$clans[] = $player->data['clan_id'];
			}
		}		

		$this->calculate_ranks();
		
		foreach($clans AS $clan_id)
		{
			$this->calculate_clan_score($clan_id, $game);
		}
		$this->calculate_clan_ranks();

		return TRUE;
	}
	
	function is_player_winner(&$player) // overridden by custom league
	{
		// winner-check: In standard settlement leagues, this is the winner flag
		return $player->is_winner();
	}
	
	function get_time_bonus($rank, $settle_time_bonus_score)
	{
		if($rank > 20)
			return 0;
		else
			return round($settle_time_bonus_score / 20.0 * (21 - $rank));
	} 
	
	function get_game_user_ranking($league_id, $scenario_id, $filter)
	{
		global $database;
		
			//what this does is:
		//- outer query: get all games (for this league/scen) joined with all players/users
		//- inner query: get just the best game for the current user (->order by frame asc, limit 1)
		//together: from all the game/user-pairs, take only those which are "best games" for the current scenario for at least one user
		
		//TODO: if its too slow (if MySQL doesn't optimize the ORDER BY frame ASC LIMIT 1):
		//try a MIN(frame) in the inner subquery, check for equal frames between the query and subquery
		//and use FIRST to get a random data in the main query for more than one game with equal frames (is this consisten then???)
		
		/*return $database->get_array("SELECT frame, gp.user_id, g.id, gp.player_id FROM lg_games AS g
			JOIN lg_game_leagues AS gl ON g.id =gl.game_id
			JOIN lg_game_players AS gp ON g.id =gp.game_id
			WHERE gl.league_id = '".$this->data['id']."' AND g.scenario_id = '".$scenario->data['id']."'
			AND gp.user_is_deleted=0 AND g.status='ended' AND g.is_revoked=0
			AND (SELECT g2.id FROM lg_games AS g2
			JOIN lg_game_leagues AS gl2 ON g2.id =gl2.game_id
			JOIN lg_game_players AS gp2 ON g2.id =gp2.game_id
			WHERE gl2.league_id = '".$this->data['id']."' AND g2.scenario_id = '".$scenario->data['id']."'
			AND gp2.user_id = gp.user_id AND g2.status='ended' AND g2.is_revoked=0
			ORDER BY frame ASC, g.id DESC LIMIT 1) = g.id
			ORDER BY frame ASC");*/

		// PETER: It actually works even better if we get rid of the dependent subquery altogether. 
		//        This way it's just MySQL doing some work on a temporary table.
// 		return $database->get_array("SELECT sq.frame, gp.user_id, g.id, gp.player_id FROM
// 			(SELECT MIN(frame) AS frame, gp.user_id
// 				FROM lg_games AS g
// 				JOIN lg_game_leagues AS gl ON g.id =gl.game_id AND gl.league_id = '$league_id'
// 				JOIN lg_game_players AS gp ON g.id =gp.game_id AND gp.user_is_deleted=0
// 				WHERE g.scenario_id = '$scenario_id' AND g.status='ended' AND g.is_revoked=0 AND $filter
// 				GROUP BY gp.user_id
// 			) AS sq
// 			JOIN lg_games AS g ON g.frame =sq.frame
// 			JOIN lg_game_leagues AS gl ON g.id =gl.game_id AND gl.league_id = '$league_id'
// 			JOIN lg_game_players AS gp ON g.id =gp.game_id AND gp.user_is_deleted=0 AND gp.user_id = sq.user_id
// 			WHERE g.scenario_id = '$scenario_id' AND g.status='ended' AND g.is_revoked=0 AND $filter
// 			GROUP BY gp.user_id
// 			ORDER BY frame ASC");

		// PETER: This one performs really good, but it feels like I'm relying on unspecified behaviour...
		return $database->get_array("SELECT * FROM
			(SELECT g.frame, g.id, gp.user_id, gp.player_id FROM
				(SELECT frame, id FROM lg_games 
					WHERE scenario_id = '$scenario_id' AND status='ended' AND is_revoked=0 AND $filter
					ORDER BY frame ASC) AS g
				JOIN lg_game_leagues AS gl ON g.id = gl.game_id AND gl.league_id = '$league_id'
				JOIN lg_game_players AS gp ON g.id = gp.game_id AND gp.user_is_deleted=0
				GROUP BY gp.user_id) AS sub
			ORDER BY frame ASC");
	}
	
	
	//recalculate all settle-scores for this scenario:
	function calculate_scores(&$game, &$scenario)
	{
		global $database;
		
		$this->update_game_delete_score($this->data['id'],$scenario->data['id']);

		$a = $this->get_game_user_ranking($this->data['id'], $scenario->data['id'], "id != '".$game->data['id']."'");
		//print_a($a,'a');
			
		//get new frames/ranks:
		$b = $this->get_game_user_ranking($this->data['id'], $scenario->data['id'], "1");

		//print_a($b,'b');

		//delete all old game-scores:	
		$database->query("DELETE lg_game_scores FROM lg_game_scores, lg_game_leagues, lg_games
			WHERE lg_game_leagues.game_id = lg_game_scores.game_id
				AND lg_games.id = lg_game_scores.game_id
				AND lg_game_leagues.league_id = '".$this->data['id']."'
				AND lg_games.scenario_id = '".$scenario->data['id']."'");
				
		//loop through all new scores, add new score-value and subtract old score-value
		$current_rank = 0;
		$rank_buffer = 0;
		$last_frame = 999999999999999;
		foreach($b AS $game_new)
		{
			//
			if($last_frame != $game_new['frame'])
			{
				$current_rank += 1 + $rank_buffer;
				$rank_buffer=0;
			}
			else
				$rank_buffer++;
			$last_frame = $game_new['frame'];
			
			$score = & new score();
			$score->load_data($game_new['user_id'],$this->data['id']);
			
			//get the old rank:
			$current_old_rank = 0;
			//if there are multiple players having the same game as their best game -> buffer ranks
			//e.g.: Ranking: 1: Sven+Peter, 3: kk
			$old_rank_buffer = 0;
			$old_last_frame = 999999999999999;
			$old_rank_found = false;
			if(is_array($a))
			{
				foreach($a AS $game_old)
				{
					if($old_last_frame != $game_old['frame'])
					{
						$current_old_rank += 1 + $old_rank_buffer;
						$old_rank_buffer=0;
					}
					else
						$old_rank_buffer++;
					$old_last_frame = $game_old['frame'];					
					
					if($game_old['user_id'] == $game_new['user_id'])
					{
						$old_rank_found = true;
						break;
					}
				}
			}
			
			$old_score = $score->data['score']; //keep old player score
			
			//if there was an old rank: subtract old bonus
			//if there was none, add base-score
			if(true == $old_rank_found)
				$score->data['score'] -= $this->get_time_bonus($current_old_rank,$scenario->data['settle_time_bonus_score']);
			else
				$score->data['score'] += $scenario->data['settle_base_score'];
				
			$score->data['score'] += $this->get_time_bonus($current_rank,$scenario->data['settle_time_bonus_score']);
			
			$score->save();
			
			//print_a($scenario, 'scen');
			//print_a($score,'score'.$current_rank);
			
			
			//insert score per player into the database:
			$game_score_data = array();
			$game_score_data['game_id'] = $game_new['id'];
			$game_score_data['player_id'] = $game_new['player_id'];
			$game_score_data['league_id'] = $this->data['id'];
			$game_score_data['score'] = $scenario->data['settle_base_score'] + $this->get_time_bonus($current_rank,$scenario->data['settle_time_bonus_score']);
			$game_score_data['settle_rank'] = $current_rank;
			
			
			$game_score_data['old_player_score'] = $old_score;//keep old player score
			//WARNING: all old_player_scores get deleted eventually - it's just used to calculate the GameScore sent in GameEnd!
			
			$database->insert_update('lg_game_scores', $game_score_data);
			
			$this->update_game($game_score_data);
			
			//print_a($game_score_data,'game_score'.$current_rank);
		}
	}
	
	
	//not for regular use
	function recalculate_all_scores()
	{
		global $database;
		
		//delete all old game-scores:
		//WARNING: this deletes all old_player_scores
		$database->query("DELETE lg_game_scores FROM lg_game_scores, lg_game_leagues 
			WHERE lg_game_leagues.game_id = lg_game_scores.game_id
				AND lg_game_leagues.league_id = '".$this->data['id']."'");
		$database->query("UPDATE lg_scores SET score=0 WHERE league_id = '".$this->data['id']."'");
		
		$a = $database->get_array("SELECT DISTINCT g.scenario_id AS id FROM lg_games AS g
			JOIN lg_game_leagues AS gl ON g.id = gl.game_id
			JOIN lg_league_scenarios AS ls ON g.scenario_id = ls.scenario_id
			AND ls.league_id = '".$this->data['id']."' AND gl.league_id = '".$this->data['id']."'");

		foreach($a AS $scenario_id)
		{
			$scenario = new scenario();
			$scenario->load_data($scenario_id['id']);
			
			$this->update_game_delete_score($this->data['id'],$scenario->data['id']);
			
			$b = $this->get_game_user_ranking($this->data['id'],$scenario->data['id'], "1");
					
			//loop through all new scores, add new score-value and subtract old score-value
			$current_rank = 0;
			//if there are multiple players having the same game as their best game -> buffer ranks
			//e.g.: Ranking: 1: Sven+Peter, 3: kk			
			$rank_buffer = 0;
			$last_frame = 999999999999999;
			foreach($b AS $game_new)
			{
				if($last_frame != $game_new['frame'])
				{
					$current_rank += 1 + $rank_buffer;
					$rank_buffer=0;
				}
				else
					$rank_buffer++;
				$last_frame = $game_new['frame'];
					
				$score = & new score();
				$score->load_data($game_new['user_id'],$this->data['id']);
				$game_score = $scenario->data['settle_base_score'] + $this->get_time_bonus($current_rank,$scenario->data['settle_time_bonus_score']);
				$score->data['score'] += $game_score;
				
				$score->save();
				
				//insert score per player into the database:
				$game_score_data = array();
				$game_score_data['game_id'] = $game_new['id'];
				$game_score_data['player_id'] = $game_new['player_id'];
				$game_score_data['league_id'] = $this->data['id'];
				$game_score_data['score'] = $game_score;
				$game_score_data['settle_rank'] = $current_rank;
				//$game_score_data['old_player_score'] = $old_score;
				$database->insert_update('lg_game_scores', $game_score_data);	
				
				$this->update_game($game_score_data);	
				
			//	print_a($b);			echo "###";
			}
			//echo "scen: ".$scenario->data['id'];  echo "<hr>";
		}

		$this->calculate_ranks();
		
		$log = new log();
		$log->add("settle-league (id: ".$this->data['id'].") scores and ranks recalculated");
	}
	
	
	//remove score-values for all games for this scenario in this league first
	function update_game_delete_score($league_id, $scenario_id)
	{
		global $database;
		
		// (PETER: gp.player_id seems to never be 0?)
		$database->query("UPDATE lg_games AS g 
			JOIN lg_game_players AS gp ON gp.game_id = g.id AND gp.player_id
			JOIN lg_game_leagues AS gl ON gl.game_id = g.id
			SET g.no_settle_rank = 1, g.settle_rank = NULL, g.settle_score = NULL
			WHERE g.scenario_id = '$scenario_id'
			AND gl.league_id = '$league_id'");
	}
	
	//save settle-score and rank to game
	function update_game(&$game_score_data)
	{
		global $database;	
		
		$database->query("UPDATE lg_games 
			SET no_settle_rank = 0, settle_score = '".$game_score_data['score']."', settle_rank = '".$game_score_data['settle_rank']."'
			WHERE id = '".$game_score_data['game_id']."'");	
	}
	
	
	//get the score a player would win, if his team wins
	function get_winner_score_and_simulate_evaluation(&$game, $winner_player_id)
	{
		//get currenct score of this player for this scenario
		global $database;
		$scenario = new scenario();
		$scenario->load_data($game->data['scenario_id']);
		
		$a = $database->get_array("SELECT score FROM lg_game_scores AS gs
			JOIN lg_games AS g ON g.id = gs.game_id
			JOIN lg_game_players AS gp ON gp.player_id = gs.player_id AND gp.game_id = g.id
			WHERE gs.league_id = '".$this->data['id']."'
			AND g.scenario_id = '".$database->escape($game->data['scenario_id'])."'
			AND gp.user_id = (SELECT gp2.user_id FROM lg_game_players AS gp2
				WHERE gp2.player_id = '".$database->escape($winner_player_id)."'
				AND gp2.game_id =  '".$database->escape($game->data['id'])."')");

		return $scenario->data['settle_base_score'] + $scenario->data['settle_time_bonus_score'] - $a[0]['score'];
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
	
	function apply_inactivity_malus()
	{
		//no inactivity-malus in settle-league
	}
}


?>