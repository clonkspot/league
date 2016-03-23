<?php

include_once('league.class.php');

class league_melee extends league
{
	
	function evaluate(&$game, &$scenario)
	{	
		//check if the league-type fits for the scenario-type:
		if($scenario->data['type'] != 'melee' && $scenario->data['type'] != 'team_melee')
		{
			//this should not happen because the game should not be in this league then (see game::create())
			$this->error = 'error_wrong_league_type';
			return FALSE;
		}
		
		//less than one minute: do not evaluate
		if($game->data['duration'] < 60)
		{
			$log = new log();
			$log->add_game_info("game not evaluated: too short (less than 1 minute)",$game->data['csid']);
			$this->error = 'error_game_too_short';
			return FALSE;
		}
		
		
		//less score for short games:
		//for games between 1 and 5 minutes, half to full score, linear scaling
		$duration_factor = 1;
		if($game->data['duration'] < 5 * 60)
		{
			$duration_factor = 0.5 * (1 + ($game->data['duration'] - 60) / (4 * 60));
		}
		
		//get all teams
		$teams = $game->get_teams();
		$winner_teams = array();
		$looser_teams = array();
		//this foreach with object-references works with php5-only i think...
		foreach($teams AS $team)
		{
			$team->temp_game_score = 0;
			if($team->is_winner())
			{
				$winner_teams[] = $team;
			}
			else
			{
				$looser_teams[] = $team;
			}
		}
		
		//check if there is at least one winner-team, otherwise delete the game from the leagues 
		//(would make no sense to evaluate such a game and cancelled games could be handeled that way.)
		//also check for a looser
		if(0 == count($winner_teams) || 0 == count($looser_teams))
		{
			$team_debug_info = var_export($teams,TRUE);
			$log = new log();
			if(count($winner_teams))
			{
				$log->add_error("game: (id: ".$game->data['id'].") end: no looser-team -> delete game-league-data (leagues set: league-id: ".$this->data['id']." - reference: ".$game->reference->get_ini()
					." - team-data: $team_debug_info");
				$this->error = 'error_no_looser';
			}
			else
			{
				$log->add_error("game: (id: ".$game->data['id'].") end: no winner-team -> delete game-league-data (leagues set: league-id: ".$this->data['id']." - reference: ".$game->reference->get_ini()
				." - team-data: $team_debug_info");
				$this->error = 'error_no_winner';
			}
			
			return FALSE;
		}
		
		// For players to receive bonus points, the game must have 2 teams of roughly the same size
		$grant_bonus = false;
		if(1 == count($winner_teams) && 1 == count($looser_teams) && 
			abs(count($winner_teams[0]->players) - count($looser_teams[0]->players)) <=
			max(count($winner_teams[0]->players), count($looser_teams[0]->players)) / 4)
		{
			$grant_bonus = true;
			
			// For each player, check all opponents for a similar bonus in the recent past
			$since = time() - 7 * 24 * 60 * 60;
			foreach($teams AS $team) if(!$team->is_winner()) foreach($team->players AS $player)
			{
				$plr_cnt = 0; $bonus_cnt = 0;
				foreach($teams AS $team2) if($team2->is_winner()) foreach($team2->players AS $player2)
				{	
					$plr_cnt = $plr_cnt + 1;
					if(!$this->got_bonus_playing_against($player->data['id'], $player2->data['id'], $this->data['id'], $since))
						$bonus_cnt = $bonus_cnt + 1;
				}
				$player->bonus_factor = $bonus_cnt / $plr_cnt;
			}
			
		}
		
		//TODO lock score-table?
		global $database;
		$database->query("LOCK TABLE lg_scores WRITE, lg_game_scores WRITE, lg_log WRITE"); //BE AWARE TO USE ONLY LOCKED TABLES 
		

		// Calculate the team scores		
		$this->calculate_team_scores($winner_teams, $looser_teams);

		
		foreach($teams AS $team)
		{
			foreach($team->players AS $player)
			{
			
				// Load existing score
				$score = $player->get_score($this->data['id']);
				$old_score = $score->data['score'];
				
				// Determine scoring for this player
				$bonus_score = 0;
				if($this->data['custom_scoring'] == 'Y')
					$game_score = $player->player_data['performance'];
				else {
				
					// Calculate using ELO formula
					$game_score = $this->calculate_player_score($team, $old_score, $duration_factor);
					
					// Bonus? Only for special games and losses.
					if($grant_bonus && $game_score < 0) {
						// Less than half of what was lost, capped by league and individual limit
						$bonus_score = intval(min($player->bonus_factor * min(
								-$game_score / 2,
								$this->data['bonus_max']),
							$score->data['bonus_account']));
						$log = new log();
						$log->add(
							"game id=".$game->data['id'].
							", lid=".$this->data['id'].
							", uid=".$player->data['id'].
							", score=".$game_score.
							", factor=".$player->bonus_factor.
							", bacc=".$score->data['bonus_account'].
							", bonus=".$bonus_score
							);
					}
				}

				// Safe the scoring data
				$game_score_data = array();
				$game_score_data['game_id'] = $game->data['id'];
				$game_score_data['player_id'] = $player->player_data['player_id'];
				$game_score_data['league_id'] = $this->data['id'];
				$game_score_data['score'] = $game_score;
				$game_score_data['bonus'] = $bonus_score;
				$game_score_data['old_player_score'] = $old_score;
				$database->insert('lg_game_scores', $game_score_data);
				
				// Update score
				$score->data['score'] += $game_score + $bonus_score;
				$score->data['bonus_account'] -= $bonus_score;
				
				// Statistics
				$score->data['date_last_game'] = time();
				$player->data['date_last_game'] = time();
				if($team->is_winner())
				{
					$score->data['games_won']++;
					$player->data['games_melee_won']++;
				}
				else
				{
					$score->data['games_lost']++;
					$player->data['games_melee_lost']++;
				}
				$score->data['duration'] += $game->data['duration'];
				
				$score->save(); 
			}
		}
		
		
		$database->query("UNLOCK TABLES");
	
		
		//no need to lock the player-table that way.
		//because of multithreading, there could be wrong game-counts in the user-data, but ignore that for now.
		//they can be recalculated if it really gets a problem...
		$players = array();
		$clans = array();
		foreach($teams AS $team)
		{
			foreach($team->players AS $player)
			{
				$player->save();
				$players[] = $player;
				
				if($player->data['clan_id'] != 0 && false == in_array($player->data['clan_id'],$clans))
				{
					$clans[] = $player->data['clan_id'];
				}
			}
		}		

		$this->calculate_ranks();
		
		foreach($clans AS $clan_id)
		{
			$this->calculate_clan_score($clan_id, $game);
		}
		$this->calculate_clan_ranks();

		//$this->calculate_trends($players);	
		
			
		
		return TRUE;
	}
	
	
	
	//get the score a player would win, if his team wins
	function get_winner_score_and_simulate_evaluation(&$game, $winner_player_id)
	{
		//get all teams
		$teams = $game->get_teams();
		$winner_teams = array();
		$looser_teams = array();
		//this foreach with object-references works with php5-only i think...
		foreach($teams AS $team)
		{
			$is_winner_team = false;
			foreach($team->players AS $player)
			{
				if($player->player_data['player_id'] == $winner_player_id)
				{
					$winner_teams[] = $team;
					$is_winner_team = true;
					break;
				}
				
			}
			
			if(false == $is_winner_team)
				$looser_teams[] = $team;
		}
		
		$this->calculate_team_scores($winner_teams, $looser_teams);
		
		foreach($teams AS $team)
		{
			foreach($team->players AS $player)
			{
				if($player->player_data['player_id'] == $winner_player_id)
				{
					$score = $player->get_score($this->data['id']);
					$old_score = $score->data['score'];
					return $this->calculate_player_score($team, $old_score);
				}
			}
		}
		
		//should never happen (winner-id not found)
		return 0;
	}
	
	// Calculates the actual score for a player based on the team score
	function calculate_player_score($team, $old_score, $factor = 1.0)
	{
		// Apply newbie protection and bonus factor
		$game_score = $this->apply_newbie_protection($old_score,
			$team->temp_game_score * $this->get_bonus_factor($old_score));
	
		//apply factor (e.g. duration) and apply rounding
		$game_score = round($game_score * $factor);
		
		return $game_score;		
	}
	
	// Writes team scores into temp_game_score members of teams
	function calculate_team_scores(&$winner_teams, &$looser_teams)
	{
		
		// Reset temp_game_score
		foreach($winner_teams AS $team)
		{
			$team->temp_game_score = 0;
		}
		foreach($looser_teams AS $team)
		{
			$team->temp_game_score = 0;
		}
				
		// Sum up scores for all team-team-encounters
		$team_count_factor = sqrt(max(count($winner_teams), count($looser_teams)));
		foreach($winner_teams AS $w_team)
		{
			foreach($looser_teams AS $l_team)
			{
				$game_score = $this->calculate_game_score($w_team, $l_team);
				if(0 == count($l_team->players))
					$team_balance_factor = 1;
				else
					$team_balance_factor = sqrt(count($w_team->players) /  count($l_team->players));
				$w_team->temp_game_score += $game_score / $team_count_factor / $team_balance_factor;
				$l_team->temp_game_score -= $game_score / $team_count_factor * $team_balance_factor;
			}
		}
	
	}
	
	//params: winner-team, looser-team
	//return score for a "virtual game" just between these two...
	//set 25 as standard-value for winning-score.
	function calculate_game_score(&$w_team, &$l_team)
	{
		return 50 * (1-$this->winning_propability($w_team, $l_team, 0, 0));
	}
	function winning_propability(&$w_team, &$l_team, $offset_winner , $offset_looser)
	{
		//print_a($w_team->players); echo count($w_team->players);
		//print_a($l_team->players); echo count($w_team->players)."#";
		if($offset_winner == count($w_team->players))
			return 0;
		elseif($offset_looser == count($l_team->players))
			return 1;
		
		$p = $this->get_elo_expected_result($w_team->players[$offset_winner]->get_score($this->data['id'])->get_value(), $l_team->players[$offset_looser]->get_score($this->data['id'])->get_value());
		$pw = $this->winning_propability($w_team, $l_team, $offset_winner+1, $offset_looser);
		$pl = $this->winning_propability($w_team, $l_team, $offset_winner, $offset_looser+1);
		//echo "##p: $p pl: $pl, pw: $pw = ".($p * $pw + (1-$p) * $pl);
		return $p * $pw + (1-$p) * $pl;
	}
	
	function get_elo_expected_result($winner_score, $looser_score)
	{
		//from old league...
		$result = 1 / (pow(10,-($winner_score-$looser_score)/400) + 1); //formula from football-rating...
		return (1 - $result);
	}
	
	//get extra score if you have less than 1400...
	function get_bonus_factor($player_score)
	{
		//not used now.
		/*$factor = (1400 - $player_score) / 400;
		if($factor > 1)
			$factor = 1;
		if($factor < 0)
			$factor = 0;
		return (1 + $factor);*/
		return 1;
	}
	
	
	//no one should get a score below 0 and newbies shouldn't loose that much.
	function apply_newbie_protection($player_score, $game_score)
	{
		if($game_score > 0)
			return $game_score;
		
		if($player_score < 300) //300 is the protection-limit for now
			$game_score = round($game_score * ($player_score / 300.0)); //just linear for now
			
		//do no allow scores below zero:
		if($player_score - $game_score < 0)
			return $player_score;
			
		return $game_score;
	}
	
	//function calculate_ranks() in league.php
	
	
	//calculate trends for the given players:
	//add up the scores for the last 7 days, if the outcome is positive: trend up, else: trend down
	/*function calculate_trends(&$players)
	{
		global $database;
		$time = time() - 7 * 24 * 60 * 60;
		
		foreach($players AS $player)
		{
			$a = $database->get_array("SELECT SUM(score) AS sum FROM lg_game_scores AS gs
				JOIN lg_games AS g ON g.id = gs.game_id
				JOIN lg_game_players AS gp ON gp.game_id = g.id AND gs.player_id = gp.player_id

				WHERE gs.league_id = '".$this->data['id']."'
				AND gs.player_id = '".$player->player_data['player_id']."'
				AND g.date_ended > $time");

			foreach($a AS $sum_data)
			{
				$score = $player->get_score($this->data['id']);
				
				if($sum_data['sum'] == 0)
					$score->data['trend'] = 'none';
				else if($sum_data['sum'] > 0)
					$score->data['trend'] = 'up';
				else
					$score->data['trend'] = 'down';
					
				$score->save();
			}
		}
	}*/
	
	
	//calculate trends for all melee leagues. to be done by cronjob.
	function calculate_trends()
	{
		global $database;
		//first: reset all trends:
		$database->query("UPDATE lg_scores
		JOIN lg_leagues AS l ON lg_scores.league_id = l.id 
		SET trend = 'none' 
		WHERE l.type = 'melee'");
		
		
		$time = time() - 7 * 24 * 60 * 60;
		
		$a = $database->get_array("SELECT SUM(score) AS sum, gp.user_id, gs.league_id
			FROM lg_game_scores AS gs
			JOIN lg_leagues AS l ON gs.league_id = l.id
			JOIN lg_games AS g ON g.id = gs.game_id
			JOIN lg_game_players AS gp ON gs.player_id = gp.player_id AND gs.game_id = gp.game_id

			WHERE l.type = 'melee'
			AND g.date_ended > $time
			GROUP BY gp.user_id");

			$score = new score();
			if(is_array($a))
			{
				foreach($a AS $sum_data)
				{
					$score->load_data($sum_data['user_id'],$sum_data['league_id']);
					
					if($sum_data['sum'] == 0)
						$score->data['trend'] = 'none';
					else if($sum_data['sum'] > 0)
						$score->data['trend'] = 'up';
					else
						$score->data['trend'] = 'down';
						
					$score->save();
				}
			}
	}
	
	//calculate trends for all melee leagues. to be done by cronjob.
	function calculate_clan_trends()
	{
		global $database;
		//first: reset all trends:
		$database->query("UPDATE lg_clan_scores
		JOIN lg_leagues AS l ON lg_clan_scores.league_id = l.id 
		SET trend = 'none' 
		WHERE l.type = 'melee'");
		
		
		$time = time() - 7 * 24 * 60 * 60;
		
		//WARNING: logic of get_clan_rank_score_percentage() hacked in here, too
		$a = $database->get_array("SELECT SUM(IF(sc.rank=1,gs.score/3,IF(sc.rank=2,gs.score/3,gs.score/sc.rank))) AS sum,
		    u.clan_id, gs.league_id, sc.rank
			FROM lg_game_scores AS gs
			JOIN lg_leagues AS l ON gs.league_id = l.id
			JOIN lg_games AS g ON g.id = gs.game_id
			JOIN lg_game_players AS gp ON gs.game_id = gp.game_id
			JOIN lg_users AS u ON u.id = gp.user_id
			JOIN lg_scores AS sc ON sc.user_id = u.id AND sc.league_id = l.id

			WHERE l.type = 'melee' AND clan_id > 0
			AND g.date_ended > $time
			GROUP BY u.clan_id");
			if(is_array($a))
			{
				foreach($a AS $sum_data)
				{
					$trend_data = array();
					if($sum_data['sum'] == 0)
						$trend_data['trend'] = 'none';
					else if($sum_data['sum'] > 0)
						$trend_data['trend'] = 'up';
					else
						$trend_data['trend'] = 'down';
						
					$database->update_where('lg_clan_scores',"league_id = '".$sum_data['league_id']."' AND clan_id = '".$sum_data['clan_id']."'",$trend_data);
				}
			}
	}
	

	function apply_inactivity_malus()
	{
    
    	// Find leagues with decay
		global $database;
		$a = $database->get_array("SELECT l.* FROM lg_leagues l
			WHERE l.date_start <= '".time()."' AND l.date_end >= '".time()."'
			AND type = 'melee'
			AND l.score_decay > 0
			AND GREATEST(l.date_last_decay, l.date_start) + l.decay_interval <= ".time());
    	
    	// No leagues with decay?
    	if(!is_array($a) || count($a) == 0)
    		return;

		$log = new log();
		foreach($a as $league) 
		{
		
			// Apply decay
			$decay_sql = "LEAST(score, " . $database->escape($league["score_decay"]) . ")";
			$bonus_account_max = $database->escape($league["bonus_account_max"]);
			$database->query(
				"UPDATE lg_scores
				  SET bonus_account = LEAST(bonus_account + $decay_sql, $bonus_account_max),
				      score = score - $decay_sql
				  WHERE league_id = ". $database->escape($league["id"]));
			
			// Log that we did it
			$log->add("Applied ranking decay to league ".$league["id"].": ".$database->get_affected_rows()." scores affected");
			$database->query(
				"UPDATE lg_leagues
				  SET date_last_decay = ".time()."
				  WHERE id = ".$database->escape($league["id"]));
			
        }
        
	}
	
	function got_bonus_playing_against($plrid, $plr2id, $league_id, $since) {
		global $database;
		
		$plrid_sql = $database->escape($plrid);
		$plr2id_sql = $database->escape($plr2id);
		$league_id_sql = $database->escape($league_id);
		$since_sql = $database->escape($since);
		
		// Find games where
		// 1. both players participated
		// 2. the first player got bonus
		// 3. the second player won
		// 4. it wasn't revoked and happend in the given time frame
		$a = $database->get_array(
			"SELECT g.id
			 FROM lg_games g
			 JOIN lg_game_players gp ON gp.game_id = g.id AND gp.user_id = $plrid_sql
			 JOIN lg_game_players gp2 ON gp2.game_id = g.id AND gp2.user_id = $plr2id_sql AND gp2.status = 'won'
			 JOIN lg_game_scores gs ON gs.league_id = $league_id_sql AND gs.game_id = g.id AND gs.player_id = gp.player_id
			 WHERE g.date_created > $since_sql AND g.is_revoked = 0 AND gs.bonus > 0");
    	return is_array($a) && count($a) > 0;
	}
	
}

?>
