<?php

class score
{
	
	var $data;
	
	function load_data($user_id,$league_id)
	{
		global $database;
		$a = $database->get_array("SELECT * FROM lg_scores
			WHERE user_id = '".$database->escape($user_id)."'
			AND league_id = '".$database->escape(
			$league_id)."'");
		if(!$a[0])
			return false;
		$this->data = $a[0];
		return true;
	}
	
	function create($user_id, $league_id)
	{
		//create new score:
		$score_data = array();
		$score_data['league_id'] = 	$league_id;
		$score_data['user_id'] = $user_id;
		$score_data['score'] = 0;
		$score_data['date_last_game'] = time();
		global $database;
		$database->insert('lg_scores', $score_data);
		$this->data = $score_data;
		return true;
	}
	
	function save()
	{
		global $database;
		return $database->update_where('lg_scores',
			"user_id = '".$this->data['user_id']."' AND league_id = '".$this->data['league_id']."'",
			$this->data);
	}
	
	function get_value()
	{
		return $this->data['score'];
	}
	
	function get_rank_symbol()
	{
		global $database;
		$a = $database->get_array("SELECT * FROM lg_scores AS sc
		JOIN lg_rank_symbols AS rs ON((sc.rank >= rs.rank_min AND sc.rank <= rs.rank_max) 
			OR (sc.score >= rs.score_min AND sc.score <= rs.score_max))
		WHERE user_id = '".$database->escape($this->data['user_id'])."'
			AND league_id = '".$database->escape($this->data['league_id'])."'
		ORDER BY rank_number ASC
		LIMIT 1");
		if(is_array($a))
			return $a[0];
		else
			return false;
	}
	
	
	
}


?>
