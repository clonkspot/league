<?php

require_once('league.class.php');

class news_statistics
{


	function news_statistics()
	{
		
	}


	function gather_statistics()
	{
		global $database;
		//save values for news-statistics:
		//oc-version
		$database->query("INSERT INTO lg_news_statistics SET k = 'oc_version', v = (SELECT version FROM lg_products WHERE name = 'OpenClonk')
		ON DUPLICATE KEY UPDATE v = VALUES(v)");
		//running games:
		$database->query("INSERT INTO lg_news_statistics SET k = 'games_running', v = (SELECT COUNT(id) FROM lg_games WHERE status='lobby' OR status='running')
		ON DUPLICATE KEY UPDATE v = VALUES(v)");
		//open games:
		$database->query("INSERT INTO lg_news_statistics SET k = 'games_open', v = (SELECT COUNT(id) FROM lg_games WHERE status='lobby')
		ON DUPLICATE KEY UPDATE v = VALUES(v)");
		
		//league games in the last 24 hours:
		$database->query("INSERT INTO lg_news_statistics SET k = 'league_games_last_24_hours', v = (SELECT count(id) FROM lg_games WHERE type != 'noleague' AND date_created > UNIX_TIMESTAMP() - 24*60*60)
		ON DUPLICATE KEY UPDATE v = VALUES(v)");		
		//games in the last 24 hours:
		$database->query("INSERT INTO lg_news_statistics SET k = 'games_last_24_hours', v = (SELECT count(id) FROM lg_games WHERE date_created > UNIX_TIMESTAMP() - 24*60*60)
		ON DUPLICATE KEY UPDATE v = VALUES(v)");		
		
		//max. games in the last 24 hours:
		$a = $database->get_array("SELECT v FROM lg_news_statistics WHERE k = 'games_last_24_hours'");
		$b = $database->get_array("SELECT v FROM lg_news_statistics WHERE k = 'games_last_24_hours_max'");
		if($a[0]['v'] > $b[0]['v'])
		{
			$database->query("INSERT INTO lg_news_statistics SET k = 'games_last_24_hours_max', v = '".$a[0]['v']."'
			ON DUPLICATE KEY UPDATE v = VALUES(v)");
		}	
				
		
		$league = new league();
		$leagues = $league->get_all_active_leagues();
		foreach($leagues AS $l_data)
		{
			$id = $l_data['id'];
			
			for($rank = 1; $rank <= 3; $rank++)
			{
				$database->query(
					"INSERT INTO lg_news_statistics
					SET k = 'league_".$id."_player_rank".$rank."', 
					    v = COALESCE((SELECT u.name FROM lg_scores AS s
									JOIN lg_users AS u ON u.id = s.user_id
									WHERE rank_order='$rank' AND league_id = '$id'), '-')
					ON DUPLICATE KEY UPDATE v = VALUES(v)");
				$database->query(
					"INSERT INTO lg_news_statistics
					SET k = 'league_".$id."_score_rank".$rank."', 
					    v = COALESCE((SELECT score FROM lg_scores AS s
									JOIN lg_users AS u ON u.id = s.user_id
									WHERE rank_order='$rank' AND league_id = '$id'), 0)
					ON DUPLICATE KEY UPDATE v = VALUES(v)");
			}
		}
		
		
		//count of players online:
		$database->query("INSERT INTO lg_news_statistics SET k = 'players_online', v = (SELECT COUNT(id) FROM lg_game_players AS gp
		JOIN lg_games AS g ON gp.game_id = g.id
		WHERE (g.status = 'lobby' OR g.status = 'running'))
		ON DUPLICATE KEY UPDATE v = VALUES(v)");
	}
	
	function get_news_statistics_text()
	{
		global $database;
		$a = $database->get_array("SELECT * FROM lg_news_statistics");
		$text = "";
		if(is_array($a))
		{
			foreach($a AS $data)
			{
				$text.=$data['k']."=".$data['v']."\n";
			}
		}
		return $text;
	}

}

?>
