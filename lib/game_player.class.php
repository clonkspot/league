<?php

include_once('user.class.php');
include_once('score.class.php');

//player-data for _one_ specific game
class game_player extends user
{
	var $player_data;
	
	var $scores;
	
	var $bonus_factor;
	
	function __construct()
	{
		parent::__construct();
		$this->scores = array();
		$this->bonus_factor = NULL;
	}
	
	//params: player_id, game_id
	function load_player_data($player_id, $game_id)
	{
		//load player-data
		global $database;
		$a = $database->get_array("SELECT * FROM lg_game_players 
			WHERE player_id = '".$database->escape($player_id)."'
			AND game_id = '".$database->escape(
			$game_id)."'");
			
		if(!$a[0])
			return false;
		$this->player_data = $a[0];
		
		//load user_data
		user::load_data($this->player_data['user_id']);
			

		return true;
	}	
	
	function save()
	{
		//save user-data:
		user::save();
		//save player-data:
		$this->save_player_data();

	}		
	
	//if you only want to save the player-data, use this. no need to mess with the user-stuff...
	function save_player_data()
	{
		global $database;
		$database->update_where('lg_game_players',
			"game_id = '".$database->escape($this->player_data['game_id'])."' 
			AND player_id = '".$this->player_data['player_id']."'"
			,$this->player_data);	
	}
	
	function delete_player_data()
	{
		global $database;
		$database->delete_where('lg_game_players',
			"game_id = '".$this->player_data['game_id']."' AND player_id = '".$this->player_data['player_id']."'");
	}
	
	function is_winner()
	{
		if($this->player_data['status'] == 'won')
			return true;
		else
			return false;
	}
	
	function get_score($league_id)
	{
		//cache values in $this->scores
		//WARNING: use this with care when changing scores! or perhaps better not use it and optimize performance when it's needed....
		/*if(is_object($this->scores[$league_id]))
			return $this->scores[$league_id];*/
		
		$score = new score();
		//load or create score from/in database
		if(false == $score->load_data($this->data['id'],$league_id))
			$score->create($this->data['id'],$league_id);

		$this->scores['league_id'] = $score;
		return $score;
	}

}



?>
