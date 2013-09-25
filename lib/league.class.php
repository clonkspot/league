<?php

include_once('table_filter.class.php');
include_once('game_reference.class.php');
include_once('score.class.php');

class league
{
	
	var $data;
	
	var $error;
	
	function get_error()
	{
		return $this->error;
	}
	
	function load_data($id)
	{
		global $database;
		$a = $database->get_array("SELECT * FROM lg_leagues WHERE id = '".$database->escape($id)."'");
		if(!$a[0])
			return false;
		$this->data = $a[0];
		return true;
	}	
	
	function save()
	{
		global $database;
		$database->update('lg_leagues',$this->data);
	}
	
	function add($data)
	{
		global $database;
		//first: add strings:
		global $language;
		$data['name_sid'] = $language->add_strings($data['name']);
		unset($data['name']);
		$data['description_sid'] = $language->add_strings($data['description']);
		unset($data['description']);
		
		if($data['date_start'] != "")
			$data['date_start'] = strtotime($data['date_start']);
		else
			$data['date_start'] = time();
		$data['date_end'] = strtotime($data['date_end']);
		$this->data['id'] = $database->insert('lg_leagues', $data);
		$log = new log();
		$log->add("league added..: ".$this->data['id']."");
	}
	
	function edit($data)
	{
		global $database;
		global $language;
		//first: add/edit strings:
		//get sid:
		$a = $database->get_array("SELECT name_sid, description_sid FROM lg_leagues
			 WHERE id = '".$database->escape($data['id'])."'");
		$language->edit_strings($a[0]['name_sid'],$data['name']);
		unset($data['name']);
		$language->edit_strings($a[0]['description_sid'],$data['description']);
		unset($data['description']);

		$data['date_start'] = strtotime($data['date_start']);
		$data['date_end'] = strtotime($data['date_end']);
		$database->update('lg_leagues',$data);
	}
	
	function delete($id)
	{
		global $database;
		$a = $database->get_array("SELECT name_sid, description_sid FROM lg_leagues WHERE id = '".$database->escape($id)."'");
		global $language;
		$language->delete_strings($a[0]['name_sid']);
		$language->delete_strings($a[0]['description_sid']);
		$database->delete('lg_leagues',$id);
		
		//TODO: delete games??
		$database->delete_where('lg_scores',"league_id = '".$database->escape($id)."'");
		$database->delete_where('lg_league_scenarios', "league_id = '".$database->escape($id)."'");
	}
	
	
	function show_add()
	{
		global $smarty;
		$smarty->assign("edit_type","add");
		
		global $database;
		$a = $database->get_array("SELECT * FROM lg_products");
		
		$smarty->assign("products", $a);		
	}
	
	
	function get_active_leagues($product_id)
	{
		global $database;
		$a = $database->get_array("SELECT * FROM lg_leagues
			WHERE date_start <= '".time()."' AND date_end >= '".time()."'
			AND product_id = '".$database->escape($product_id)."'");
		if(!$a[0])
			return false;
		return $a;
	}
	
	function get_all_active_leagues()
	{
		return $this->get_all_leagues(1);
	}

	function get_all_leagues($current)
	{
		// 0 = all leaugues, 1 = active only, 2 = old only
		$filter = "";
		switch($current)
		{
		case 1: $filter = "WHERE date_start < '".time()."' AND date_end > '".time()."'"; break;
		case 2: $filter = "WHERE NOT(date_start < '".time()."' AND date_end > '".time()."')"; break;
		}
		
		global $database; global $language;
		$a = $database->get_array("SELECT ".
			$language->get_string_with_fallback_sql('l.name_sid')." AS name,
			l.*
			FROM lg_leagues AS l
			$filter
			ORDER BY l.date_start DESC");
		if(!$a[0])
			return false;
		return $a;
	}
	
	/** get active leagues in which the scenario is allowed
	 */
	function get_active_leagues_by_scenario($product_id, $scenario_id)
	{
		global $database;
		$a = $database->get_array("SELECT l.* FROM lg_leagues AS l
			JOIN lg_league_scenarios AS ls ON ls.league_id = l.id
			WHERE date_start <= '".time()."' AND date_end >= '".time()."'
			AND product_id = '".$database->escape($product_id)."'
			AND ls.scenario_id = '".$database->escape($scenario_id)."'");
		if(!$a[0])
			return false;
		return $a;
	}	
	
	function get_active_unrestriced_leagues($product_id)
	{
		global $database;
		$a = $database->get_array("SELECT * FROM lg_leagues
			WHERE date_start <= '".time()."' AND date_end >= '".time()."'
			AND scenario_restriction = 'N'
			AND product_id = '".$database->escape($product_id)."'");
		if(!$a[0])
			return false;
		return $a;
	}
	
	function get_scenarios($league_id)
	{
		global $database;
		global $language;
		$a = $database->get_array("SELECT sc.*, ".
			$language->get_string_with_fallback_sql('sc.name_sid')." AS name
			FROM lg_scenarios AS sc
			JOIN lg_league_scenarios AS ls ON ls.scenario_id = sc.id
			WHERE ls.league_id = '".$database->escape($league_id)."'
			ORDER BY name");
		if(!$a[0])
			return false;
			
		decode_octal_array($a, 'name');	
		return $a;
	}
	
	
	function calculate_clan_ranks()
	{
		$this->calculate_ranks(TRUE);
	}
	
	
	function calculate_ranks($clan_ranking = FALSE)
	{
		global $database;
		
		$ranking_timeout_condition = "(l.ranking_timeout = 0 
			  OR s.date_last_game > ".time()." - l.ranking_timeout*24*60*60
			  OR ".time()." > l.date_end - l.ranking_timeout*24*60*60)";
		
		if($clan_ranking == FALSE)
		{
			$a = $database->get_array("SELECT s.* FROM lg_scores s 
				JOIN lg_leagues AS l ON l.id = '".$this->data['id']."'
				WHERE s.league_id = '".$this->data['id']."' AND s.user_is_deleted = 0
				AND $ranking_timeout_condition
				ORDER BY s.score DESC, s.user_id ASC");
				
			//for ranking-timeout, set all to 0
			$database->query("UPDATE lg_scores s
				JOIN lg_leagues l ON l.id = '".$this->data['id']."'
				SET s.rank = 0
				WHERE s.league_id = '".$this->data['id']."' AND NOT $ranking_timeout_condition");
		}
		else
		{
			$a = $database->get_array("SELECT s.* FROM lg_clan_scores s 
				JOIN lg_leagues AS l ON l.id = '".$this->data['id']."'
				JOIN lg_clans AS c ON c.id = s.clan_id
				WHERE s.league_id = '".$this->data['id']."'
				AND $ranking_timeout_condition
				ORDER BY s.score DESC, c.id ASC");
				
			//for ranking-timeout, set all to 0
			$database->query("UPDATE lg_clan_scores s
				JOIN lg_leagues l ON l.id = '".$this->data['id']."'
				SET s.rank = 0
				WHERE s.league_id = '".$this->data['id']."' AND NOT $ranking_timeout_condition");
		}
			
		if(!is_array($a))
			return; //no scores -> nothing to do.
			
		$current_rank = 0;
		$current_rank_order=0;
		$last_score = -99;
		$rank_buffer=0; //use this to increment the ranks following ranks used multiple times
		foreach($a AS $score_data)
		{
			if($last_score != $score_data['score'])
			{
				$current_rank += 1 + $rank_buffer;
				$rank_buffer=0;
			}
			else
				$rank_buffer++;
				
			$current_rank_order++;
			$last_score = $score_data['score'];
			
			// No update needed?
			if($current_rank == $score_data['rank'] && $current_rank_order == $score_data['rank_order'])
				continue;
			
			$score_data['rank'] = $current_rank;
			$score_data['rank_order'] = $current_rank_order;
			
			if($clan_ranking == FALSE)
			{
				$database->update_where('lg_scores',
					"user_id = '".$score_data['user_id']."' AND league_id = '".$score_data['league_id']."'",
					$score_data);
			}
			else
			{
				$database->update_where('lg_clan_scores',
					"clan_id = '".$score_data['clan_id']."' AND league_id = '".$score_data['league_id']."'",
					$score_data);
			}
				
		}
		
	}		
	
	
	function calculate_clan_score($clan_id, &$game = NULL)
	{
		global $database;

		$clan_score_data = array();
		
		if($game != NULL)
		{
			$a = $database->get_array("SELECT * FROM lg_clan_scores WHERE clan_id = '".$database->escape($clan_id)."'
				AND league_id = '".$this->data['id']."'");
			$clan_score_data=$a[0];
			$clan_score_data['games_count']++;		
			$clan_score_data['duration'] += $game->data['duration'];	
			$clan_score_data['date_last_game'] = time();
		}
		
		$clan_score_data['score'] = 0;
		$in_clan_rank = 1;
		//get score:
		$a = $database->get_array("SELECT sc.* FROM lg_scores AS sc
			JOIN lg_users AS u ON u.id = sc.user_id
			WHERE sc.league_id = '".$this->data['id']."'
			AND u.clan_id = '".$database->escape($clan_id)."'
			ORDER BY sc.rank ASC");		
		if(is_array($a))
		{
			foreach($a AS $score_data)
			{
				$clan_score_data['score'] += $this->get_clan_rank_score_percentage($in_clan_rank) * $score_data['score'];
				$in_clan_rank++;
			}
		}
		$clan_score_data['score'] = round($clan_score_data['score']);		
		
		$clan_score_data['clan_id'] = $clan_id;
		$clan_score_data['league_id'] = $this->data['id'];		
		
		// < 3 member -> set score to -1:
		$a  = $database->get_array("SELECT COUNT(id) AS count FROM lg_users AS u WHERE u.clan_id = '".$database->escape($clan_id)."'");
		if($a[0]['count'] < 3)
			$clan_score_data['score'] = -1;		
			
		$database->insert_update('lg_clan_scores', $clan_score_data);
	}
	
	function calculate_clan_stats($clan_id)
	{
		global $database;
		$clan_score_data = array();
		//get count, duration and date of last game:
		$a = $database->get_array("SELECT COUNT(DISTINCT g.id) AS count, SUM(g.duration) AS duration, MAX(g.date_ended) AS date_last_game
			FROM lg_games AS g
			JOIN lg_game_players AS gp ON gp.game_id = g.id
			JOIN lg_users AS u ON u.id = gp.user_id
			JOIN lg_game_leagues AS gl ON gl.game_id = g.id 
			WHERE gl.league_id = '".$this->data['id']."' AND u.clan_id = '".$database->escape($clan_id)."'");
		
		$clan_score_data['games_count'] = $a[0]['count'];		
		$clan_score_data['duration'] = $a[0]['duration'];	
		$clan_score_data['date_last_game'] = $a[0]['date_last_game'];

		$clan_score_data['clan_id'] = $clan_id;
		$clan_score_data['league_id'] = $this->data['id'];
			
		$database->insert_update('lg_clan_scores', $clan_score_data);	

		
		//reset flag to update stats:
		$database->query("UPDATE lg_clans 
			SET cronjob_update_stats = 0
			WHERE id = '".$database->escape($clan_id)."'");
		
		//extra call needed, done in cronjob
		//$this->calculate_clan_favorite_scenario($clan_id);
	}
	
	function calculate_clan_favorite_scenario($clan_id)
	{
		global $database;
		$clan_score_data = array();
		
		//favorite scenario:	
		$a = $database->get_array("SELECT g.scenario_id, count(DISTINCT g.id) AS cnt
			FROM lg_games AS g
			JOIN lg_game_players AS gp ON gp.game_id = g.id
			JOIN lg_users AS u ON u.id = gp.user_id
			JOIN lg_game_leagues AS gl ON g.id = gl.game_id
			WHERE gl.league_id = '".$this->data['id']."' 
			AND u.clan_id = '".$database->escape($clan_id)."' 
			GROUP BY g.scenario_id ORDER BY cnt DESC LIMIT 1");

		$clan_score_data['favorite_scenario_id'] = $a[0]['scenario_id'];
		
		$clan_score_data['clan_id'] = $clan_id;
		$clan_score_data['league_id'] = $this->data['id'];

		$database->insert_update('lg_clan_scores', $clan_score_data);		
	}
	
	function calculate_favorite_scenario($user_id)
	{
		global $database;
		$score = new score();
		$score->load_data($user_id, $this->data['id']);
		//update favorite scenario:
		$a = $database->get_array("SELECT g.scenario_id, count(DISTINCT g.id) AS cnt
		FROM lg_games AS g
		JOIN lg_game_players AS gp ON gp.game_id = g.id
		JOIN lg_game_leagues AS gl ON g.id = gl.game_id
		WHERE gl.league_id = '".$this->data['id']."' AND user_id = '".$database->escape($score->data['user_id'])."' 
		GROUP BY g.scenario_id ORDER BY cnt DESC LIMIT 1");
		$score->data['favorite_scenario_id'] = $a[0]['scenario_id'];
		$score->save(); //save score here
	}
	
	function get_clan_rank_score_percentage($rank)
	{
		//WARNING: on change: don't foregt to change SQL in calculate_clan_trends()
		switch($rank) {
			case 1:
				return 1/3;	
			case 2:
				return 1/3;
			default:
				return 1/$rank;
		}
	}	
	
	
	
	function show_edit($id)
	{
		global $smarty;
		$smarty->assign("edit_type","edit");
		
		global $database;
		$a = $database->get_array("SELECT * FROM lg_leagues
			WHERE id = '".$database->escape($id)."'");
		//get strings:
		global $language;
		$strings = $language->get_strings_by_sid($a[0]['name_sid']);
		foreach($strings AS $string)
		{
			$a[0]['name'][$string['language_id']] = $string['string']; 
		}
		$strings = $language->get_strings_by_sid($a[0]['description_sid']);
		foreach($strings AS $string)
		{
			$a[0]['description'][$string['language_id']] = $string['string']; 
		}

		//format start-and end-time in us-time-format:
		$a[0]['date_start'] = date("Y-m-d H:i:s",$a[0]['date_start']);
		$a[0]['date_end'] = date("Y-m-d H:i:s",$a[0]['date_end']);
		$smarty->assign("league",$a[0]);
		
		$a = $database->get_array("SELECT * FROM lg_products");
		
		$smarty->assign("products", $a);
		
		$smarty->assign("scenarios", $this->get_scenarios($id));		
	}
	
	function show_list($filter = NULL, $page = 0, $sort = NULL)
	{
		if(!$page)
			$page = 0;		

		global $database;
		global $language;			
		global $smarty;
		
		$where = "";	
		$valid_filters = array("p.name");
		$table_filter = new table_filter();
		$where .= $table_filter->get_where_clause($filter, $valid_filters);
		
		$per_page = 50; //TODO: set in config or somewhere else?
		$limit_start = intval($page * $per_page);

		$order = "is_current DESC, date_start DESC";
		//sort-defaults:
		$smarty->assign("default_sort_col", "date_start");
		$smarty->assign("default_sort_dir", "desc");
		
		if($sort['dir']!='desc')
			$sort['dir'] = 'asc';
			
		if($sort['col']=='type' || $sort['col']=='date_start'  || $sort['col']=='date_end' 
		  || $sort['col']=='recurrent' || $sort['col']=='scenario_restriction' 
		  || $sort['col']=='ranking_timeout'  || $sort['col']=='product_id'
		  || $sort['col']=='name' || $sort['col']=='description')
		{
			$order = $sort['col']." ".$sort['dir'];
		}			
		
		
		$a = $database->get_array("SELECT " .
			$language->get_string_with_fallback_sql('l.name_sid'). " AS name,".
			$language->get_string_with_fallback_sql('l.description_sid'). " AS description,
			l.*, p.name AS product_name, p.icon AS product_icon,
			(date_start <= '".time()."' AND date_end >= '".time()."') AS is_current
			FROM lg_leagues AS l
			LEFT JOIN lg_products AS p ON p.id = l.product_id
			WHERE 1=1 $where
			ORDER BY $order
			LIMIT $limit_start, $per_page");
			
		for($i=0;$i<count($a);$i++)
		{
			$a[$i]['scenarios'] = $this->get_scenarios($a[$i]['id']);
		}	
		
		global $smarty;
		$smarty->assign("leagues",$a);
		$smarty->assign("page_start",$limit_start+1);
		$smarty->assign("page_items_count",count($a));
		
		$a = $database->get_array("SELECT COUNT(*) AS count FROM lg_leagues l
			 LEFT JOIN lg_products AS p ON p.id = l.product_id
			 WHERE 1=1 $where");
		$smarty->assign("page",$page);
		$smarty->assign("page_count",intval(($a[0]['count']-1) / $per_page)+1);
		$smarty->assign("total_items_count",$a[0]['count']);
		
		$a = $database->get_array("SELECT * FROM lg_products");
		$smarty->assign("products", $a);
	}
	
	function show_clan_ranking($id, $filter=NULL, $page = 0, $sort = NULL, $highlight = FALSE)
	{
		$this->show_ranking($id, $filter, $page, $sort, $highlight, TRUE);
	}
	
	//if clan-id is set, show team-ranking instead of player-ranking
	function show_ranking($id, $filter=NULL, $page = 0, $sort = NULL, $highlight = FALSE, $clan_ranking = FALSE)
	{
		global $database;
		if(!$id)
		{
			//if id is not set, use first active league as default:
			$a = $database->get_array("SELECT id FROM lg_leagues
				WHERE date_start < '".time()."' AND date_end > '".time()."'
				ORDER BY id ASC LIMIT 1");
			$id = $a[0]['id'];
		}
		$league = new league();
		$league->load_data($id);
		
		if(!$page)
			$page = 0;
			
		global $smarty;
		
		$where = "";
		$clan_filter_used = FALSE;
		if($clan_ranking == FALSE)
		{
			if(is_array($filter['clan_name']))
			{
				//get clan id:
				$a = $database->get_array("SELECT id FROM lg_clans 
					WHERE name = '".$database->escape($filter['clan_name'][0])."'");
				$where .=" AND clan_id = '".$a[0]['id']."' ";
				unset($filter['clan_name']);
				$clan_filter_used = TRUE;
			}
		}
		
		
		$order = "rank_order ASC";
		//sort-defaults:
		$smarty->assign("default_sort_col", "rank");
		$smarty->assign("default_sort_dir", "asc");
		
		if($sort['dir']!='desc')
			$sort['dir'] = 'asc';
		if(!isset($sort['col']))
			$sort['col'] = 'rank';
			
		if($sort['col']=='score'  || $sort['col']=='date_last_game' 
		  || $sort['col']=='games_won' || $sort['col']=='games_lost' 
		  || ($sort['col']=='u.name' && false == $clan_ranking)  || ($sort['col']=='c.name' && true == $clan_ranking) 
		  || $sort['col']=='games_count'
		  || $sort['col']=='bonus_account' 
		  || $sort['col']=='trend' || $sort['col']=='duration' || $sort['col']=='clan_tag'
		  || $sort['col']=='favorite_scenario'
		  || ($sort['col']=='user_count' && $clan_ranking)
		  || ($sort['col']=='clan_tag' && $clan_ranking))
		{
			$order = $sort['col']." ".$sort['dir'];
		}	
		elseif($sort['col']=='rank')
			$order = "rank_order ".$sort['dir'];
		else
			unset($sort['col']);
		
		$per_page = 50; //TODO: set in config or somewhere else?
		
			
		
		//if a highlighter is set, get the right page-number:
		if($highlight)
		{
			if($clan_ranking == FALSE)
			{
				$a = $database->get_array("SELECT sc.user_id AS id, sc.rank FROM lg_scores AS sc
					WHERE sc.league_id = '".$database->escape($id)."'
					AND sc.user_id = '".$database->escape($highlight)."'
					AND sc.user_is_deleted = 0");
				$a = $database->get_array("SELECT COUNT(*) AS count FROM lg_scores AS sc
					 WHERE league_id = '".$database->escape($id)."'
					 AND rank > 0 AND sc.user_is_deleted = 0
					 AND rank < '".$a[0]['rank']."' OR (rank = '".$a[0]['rank']."' AND sc.user_id < '".$a[0]['id']."')
					 $where ");
			}
			else
			{
				$a = $database->get_array("SELECT sc.clan_id AS id, sc.rank FROM lg_clan_scores AS sc
					WHERE sc.league_id = '".$database->escape($id)."'
					AND sc.clan_id = '".$database->escape($highlight)."'");
				$a = $database->get_array("SELECT COUNT(*) AS count FROM lg_clan_scores AS sc
					 WHERE league_id = '".$database->escape($id)."'
					 AND rank > 0
					 AND rank < '".$a[0]['rank']."' OR (rank = '".$a[0]['rank']."' AND sc.clan_id < '".$a[0]['id']."')
					 $where");
			}
				 
			$page = intval($a[0]['count'] / $per_page);
		}
		
		$limit_start = intval($page * $per_page);
		
		global $language;
				
		if($clan_ranking == FALSE)
		{
			$a = $database->get_array("SELECT 
				sc.*, u.name, 
				IF(lg_scenarios.id IS NULL,'', " . $language->get_string_with_fallback_sql("lg_scenarios.name_sid") . ") AS favorite_scenario,
				(games_won + games_lost) AS games_count,
				clan.tag AS clan_tag, clan.id AS clan_id
				FROM lg_scores AS sc
				JOIN lg_users AS u ON u.id = sc.user_id
				LEFT JOIN lg_clans AS clan ON u.clan_id = clan.id
				LEFT JOIN lg_scenarios ON lg_scenarios.id = sc.favorite_scenario_id
				
				WHERE sc.league_id = '".$database->escape($id)."'
				AND rank > 0 AND sc.user_is_deleted = 0
				$where
				ORDER BY $order
				LIMIT $limit_start, $per_page");
					

			$score = new score();
			if(is_array($a))
			{
				foreach($a AS $key => $data)
				{
					$score->load_data($data['user_id'],$data['league_id']);
					$rank_symbol = $score->get_rank_symbol();
					$a[$key]['rank_icon'] = $rank_symbol['icon'];
					$a[$key]['rank_number'] = $rank_symbol['rank_number'];
				}
			}
		}
		else
		{
			$a = $database->get_array("SELECT 
				sc.*, c.name, c.id AS clan_id, c.tag AS clan_tag,
				IF(lg_scenarios.id IS NULL,'', " . $language->get_string_with_fallback_sql("lg_scenarios.name_sid") . ") AS favorite_scenario,
				(SELECT COUNT(id) FROM lg_users AS u WHERE u.clan_id = c.id) AS user_count
				FROM lg_clan_scores AS sc
				JOIN lg_clans AS c ON c.id = sc.clan_id
				LEFT JOIN lg_scenarios ON lg_scenarios.id = sc.favorite_scenario_id
					
				WHERE sc.league_id = '".$database->escape($id)."'
				AND rank > 0
				$where
				ORDER BY $order
				LIMIT $limit_start, $per_page");
		}
		
		decode_octal_array($a, 'favorite_scenario');
			

		$smarty->assign("scores",$a);
		$smarty->assign("page_start",$limit_start+1);
		$smarty->assign("page_items_count",count($a));
		
		if($clan_ranking == FALSE)
		{
			$sql = "SELECT COUNT(*) AS count FROM lg_scores AS sc ";
			if(TRUE == $clan_filter_used)
			{
				$sql .=" JOIN lg_users AS u ON u.id = sc.user_id
					JOIN lg_clans AS clan ON u.clan_id = clan.id ";
			}
			$sql .=" WHERE league_id = '".$database->escape($id)."'
				 AND rank > 0 AND sc.user_is_deleted = 0
				 $where";
			$a = $database->get_array($sql);
		}
		else
		{
			$a = $database->get_array("SELECT COUNT(*) AS count FROM lg_clan_scores AS sc
				 WHERE league_id = '".$database->escape($id)."'
				 AND rank > 0
				 $where");
				 
			$smarty->assign("clan_ranking",1);
		}
		$smarty->assign("page",$page);
		$smarty->assign("page_count",intval(($a[0]['count']-1) / $per_page)+1);
		$smarty->assign("total_items_count",$a[0]['count']);	
		
		//assign user-data if logged in:
		global $user;
		$smarty->assign("user_id",$user->data['id']);
		$smarty->assign("user_clan_id",$user->data['clan_id']);
		
		$smarty->assign("highlight",$highlight);

		// get league data, separate for current and old leagues
		global $language;
		$a = $this->get_all_leagues(1);
		$smarty->assign("leagues", $a);

		$league_data = array();
		foreach($a AS $ld)
			if($ld['id'] == $id)
				$league_data = $ld;
		
		$a = $this->get_all_leagues(2);
		$smarty->assign("old_leagues", $a);
		
		foreach($a AS $ld)
			if($ld['id'] == $id)
				$league_data = $ld;

		$smarty->assign("league",$league_data);

	}
	
	
	//if its a recurrent league, restart it:
	function restart_recurrent_leagues()
	{
		global $language;
		global $database;
		//get all ended recurrent leagues:
		$a = $database->get_array("SELECT l.*, s_name.string AS name, s_desc.string AS description
			FROM lg_leagues AS l
			LEFT JOIN lg_strings AS s_name
			ON s_name.id = l.name_sid
			LEFT JOIN lg_strings AS s_desc
			ON s_desc.id = l.description_sid
			WHERE date_start < '".time()."' AND date_end < '".time()."'
			AND recurrent = 'Y'");
			
			
		//save league-ids of leagues already done, just insert names in other languages for these:
		$already_done = array();
			
		if(is_array($a))
		{
			foreach($a AS $league_data)
			{

				
				if(false == in_array($league_data['id'], $already_done))
				{
					$database->query("UPDATE lg_leagues SET recurrent = 'N'
						WHERE id = '".$league_data['id']."'");
						
					//insert new league:
					$duration = $league_data['date_end'] - $league_data['date_start'];
					$league_data['date_start'] = $league_data['date_end'];
					$league_data['date_end'] = $league_data['date_start'] + $duration;
					$old_id = $league_data['id'];
					unset($league_data['id']);
				}
				
					$league_data['name_sid'] = $language->add_strings($league_data['name']);
					unset($league_data['name']);
					$league_data['description_sid'] = $language->add_strings($league_data['description']);
					unset($league_data['description']);
					
				if(false == in_array($league_data['id'], $already_done))
				{
					$new_id = $database->insert('lg_leagues',$league_data);
	
					$log = new log();
					$log->add("recurrent league $old_id restarted as $new_id");
				}
				$already_done[] = $league_data['id'];
			}
		}
	}
	
}


?>