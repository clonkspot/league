<?php

include_once('game_player.class.php');

//team for _one_ specific game
class game_team 
{
	var $data;
	
	var $players; //array containing all game_players of that team...
	
	var $temp_game_score; //just used during evaluation as temp-variable per team...
		
	
	function create($data)
	{
		global $database;
		$this->data = $data;
		$database->insert('lg_game_teams', $data);
		
		//next line not needed at the moment...but who knows. 
		$this->load_players();
	}
	
	function load_data($team_id,$game_id)
	{
		global $database;
		$a = $database->get_array("SELECT * FROM lg_game_teams 
			WHERE team_id = '".$database->escape($team_id)."'
			AND game_id = '".$database->escape($game_id)."'");
		if(!$a[0])
			return false;
		$this->data = $a[0];
		
		
	//	$log = new log();
	//	$log->add("loading team-data: ".print_r($this->data,true));
		
		$this->load_players();
		
		return true;
	}	
	
	function load_players()
	{
		global $database;
		//load players:
		$a = $database->get_array("SELECT player_id FROM lg_game_players 
			WHERE team_id = '".$this->data['team_id']."'
			AND game_id = '".$this->data['game_id']."'");
		$players = array();
		if($a[0])
		{
			foreach($a AS $p)
			{
				$player = new game_player();
				$player->load_player_data($p['player_id'],$this->data['game_id']);
				$players[] = $player;
			}
		}
		$this->players = $players;
	}
	
	function save()
	{
		//save team-data
		$this->save_team_data();
		
		//save data of all players:
		for($i=0;$i<count($this->players);$i++)
		{
			$this->players[$i]->save();
		}
	}	
	
	function save_team_data()
	{
		//save team-data
		global $database;
		
		//for an unknown reason not working all the time:
		$database->update_where('lg_game_teams',
			"team_id = '".$this->data['team_id']."' AND game_id = '".$this->data['game_id']."'",
			$this->data);
		
		
		//$log = new log();
		//$log->add("saving team-data: $sql".mysql_error());
	}	
	
	
	function is_winner()
	{
		if($this->data['team_status'] == 'won')
			return true;
		return false;
	}
	
	//set to true if at least one player was alive in the end (Won-Flag set)
	function calc_winner_looser()
	{
		if(is_array($this->players))
		{
			foreach($this->players AS $player)
			{
				if($player->is_winner())
				{
					$this->data['team_status'] = 'won';
					$this->save_team_data();
					return;
				}
			}
		}
		$this->data['team_status'] = 'lost';
		$this->save_team_data();
		return;
	}
	
	function get_score($league_id)
	{
		$score_sum = 0;
		foreach($this->players AS $player)
		{
			$score_sum += $player->get_score($league_id)->get_value();
		}
		return $score_sum;
	}
	
	function get_player_count()
	{
		return count($this->players);
	}
	
}

?>
