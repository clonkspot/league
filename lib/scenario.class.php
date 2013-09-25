<?php

include_once('table_filter.class.php');
include_once('game_reference.class.php');

class scenario
{
	var $data;
	
	var $versions; //stuff from lg_scenario_versions
	
	function load_data($id)
	{
		global $database;
		$a = $database->get_array("SELECT * FROM lg_scenarios WHERE id = '".$database->escape($id)."'");
		if(!$a[0])
			return false;
		$this->data = $a[0];
		
		$this->load_versions();
		
		return $this->data['id'];
	}
	
	//load data by league-id and filename
	//should be unique for settle-scenarios, used by admin2.php
	function load_data_by_league_filename($league_id, $filename)
	{
		global $database;
		$a = $database->get_array("SELECT DISTINCT sc.*
			FROM lg_scenarios AS sc
			JOIN lg_league_scenarios AS ls ON ls.scenario_id = sc.id
			JOIN lg_scenario_versions AS sv ON sv.scenario_id = sc.id
			WHERE ls.league_id = '".$database->escape($league_id)."'
			AND sv.filename = '".$database->escape($filename)."'");

		if(!$a[0])
			return false;
		$this->data = $a[0];
		
		$this->load_versions();
		
		return $this->data['id'];
	}	
	
	function load_versions()
	{
		global $database;
		$a  = $database->get_array("SELECT * FROM lg_scenario_versions WHERE scenario_id = '".$this->data['id']."'
			ORDER BY date_created");
		$this->versions= $a;
	}	
	
	function load_data_by_hash($hash,$hash_sha)
	{
		global $database;
		$a = $database->get_array("SELECT scenario_id, hash_sha FROM lg_scenario_versions 
			WHERE hash = '".$database->escape($hash)."'");
		//print_a($a);echo "$hash_sha <hr>";
		if(strtolower($hash_sha) != strtolower($a[0]['hash_sha']) || !$hash_sha)
			return false;
			
		return $this->load_data($a[0]['scenario_id']);
	}
	
	// use author and filename at the moment. 
	// Only load scenarios that aren't currently registered in a (restricted) league
	function load_unrestricted_data_by_game_reference(&$reference)
	{
		global $database;
		$sql = "SELECT scv.scenario_id AS sc_id FROM lg_scenario_versions scv
			LEFT JOIN lg_league_scenarios lsc ON lsc.scenario_id = scv.scenario_id
			WHERE scv.author = '".$database->escape(remove_quotes($reference->data['[Reference]'][0]['[Scenario]'][0]['Author']))."'
			AND scv.filename = '".$database->escape($this->get_filename_from_reference($reference))."'
			AND lsc.scenario_id IS NULL";
		$a = $database->get_array($sql);
		return $this->load_data($a[0]['sc_id']);
	}
	
	function clean_scenario_title($title)
	{
		// Some scenario authors love to use the engine bug that tags are actually interpreted
		// in the internet game listing. We won't do that, so simply strip it for our frontend.
		return preg_replace('/<[\/\w\s\d]*>/', "", remove_quotes($title));
	}
	
	function update_scenario_title(&$reference)
	{
		//update scenario-title in the current language:
		global $language;
		$title = $this->clean_scenario_title($reference->data['[Reference]'][0]['Title']);
		$strings = array();
		$strings[$language->get_current_language_id()] = $title;
		$language->edit_strings($this->data['name_sid'],$strings);	
		
		//also update icon:
		$this->data['icon_number'] = $reference->data['[Reference]'][0]['Icon'];
		$this->save();
		
		return true; //always return true
	}
	
	
	function get_filename_from_reference(&$reference)
	{
		//print_a($reference);
		//get filename from path:
		$filename = remove_quotes($reference->data['[Reference]'][0]['[Scenario]'][0]['Filename']);
		$strpos = strrpos($filename,"\\");
		if($strpos)
			$strpos+=1;
		$filename = substr($filename,$strpos);
		return $filename;
	}
	
	function save()
	{
		global $database;
		$database->update('lg_scenarios',$this->data);
		
		//TODO versions?
	}
	
	
	function add($data, $versions, $leagues)
	{
		global $database;
		//first: add strings:
		global $language;
		$data['name_sid'] = $language->add_strings($data['name']);
		unset($data['name']);
		$id = $database->insert('lg_scenarios',$data);

		foreach($versions AS $version_data)
		{
			$version_data['scenario_id'] = $id;
			$version_data['date_created'] = time(); //a new one
			$database->insert('lg_scenario_versions',$version_data);
		}
		
		$this->set_leagues($id, $leagues);
		return $id;
	}
	
	function add_version($version_data)
	{
		global $database;

		if(!$this->data['id'])
			return false;
			
		// Check for existing version
		if($database->exists("SELECT * FROM lg_scenario_versions
			WHERE scenario_id = '".$database->escape($this->data['id'])."'
			AND hash = '".$database->escape($version_data['hash'])."'
			AND hash_sha = '".$database->escape($version_data['hash_sha'])."'"))
			return true;
		
		$version_data['scenario_id'] = $this->data['id'];
		$version_data['date_created'] = time(); //a new one
		$database->insert('lg_scenario_versions',$version_data);
		
		$log = & new log();
		$log->add("Scenario ID ".$this->data['id']." got new version CRC ".$version_data['hash']." SHA ".$version_data['hash_sha']);
		return true;
	}	
	
	//removes all version-entries for a scenario
	//does _not_ delete the scenario itself!
	function delete_all_versions()
	{
		global $database;

		if(!$this->data['id'])
			return;
		
		$database->delete_where('lg_scenario_versions',"scenario_id = '".$database->escape($this->data['id'])."'");
	}		
	
	//to autocreate scenario-entries for unrestricted leagues
	//just makes sense for melees and team-melees for now...
	function add_by_reference(&$reference, $product_id)
	{
		global $database;
		//first: add title in the language set in the http-header..:
		global $language;
		$title = $this->clean_scenario_title($reference->data['[Reference]'][0]['Title']);
		$strings = array();
		$strings[$language->get_current_language_id()] = $title;
		$data = array();
		$data['name_sid'] = $language->add_strings($strings);
		
		$data['active'] = 'Y';
		//get the type by checking if teams are enabled..:
		if($reference->data['[Reference]'][0]['[Teams]'][0]['Active']=='false')
			$data['type'] = 'melee';
		else
			$data['type'] = 'team_melee';
			
		$data['product_id'] = $product_id;
		if(false == $product_id)
		{
			$log = & new log();
			$log->add_user_error("game with scenario $title has invalid product-id. version in reference invalid? reference: ".$reference->get_ini());
			return false;
		}	
		
		
		//TODO: perhaps check GOALs (do we need that?) and see if its really a melee. 
		//or forget this note and just allow any scenario where you could get killed in the end ;)
			
		$data['autocreated'] = 'Y';

		$data['icon_number'] = $reference->data['[Reference]'][0]['Icon'];
		
		$this->data = $data;
		$this->data['id'] = $database->insert('lg_scenarios',$data);
		
		$log = & new log();
		$log->add("Scenario $title added to database (id: ".$this->data['id']." type: ".$data['type'].")");
		
		$this->add_version_by_reference($reference);
		
		return true;
	}
	
	function add_version_by_reference(&$reference)
	{
		$version_data = array();
		$version_data['hash'] = $reference->data['[Reference]'][0]['[Scenario]'][0]['FileCRC'];
		$version_data['hash_sha'] = $reference->data['[Reference]'][0]['[Scenario]'][0]['FileSHA'];
		$version_data['author'] = remove_quotes($reference->data['[Reference]'][0]['[Scenario]'][0]['Author']);
		$version_data['filename'] = $this->get_filename_from_reference($reference);
		$version_data['comment'] = "(from reference)";
		
		return $this->add_version($version_data);
	}
	
	function edit($data,$versions,$scenarios_merge,$leagues)
	{
		global $database;
		//first: add/edit strings:
		//get sid:
		$a = $database->get_array("SELECT name_sid FROM lg_scenarios WHERE id = '".$database->escape($data['id'])."'");
		global $language;
		$language->edit_strings($a[0]['name_sid'],$data['name']);
		unset($data['name']);
		$database->update('lg_scenarios',$data);
		//delete all versions and re-set them:
		$database->delete_where('lg_scenario_versions', "scenario_id = '".$database->escape($data['id'])."'");
		
		foreach($versions AS $version_data)
		{
			if(!$version_data['hash'] && !$version_data['filename'])
				continue;
			$version_data['scenario_id'] = $data['id'];
			if(!$version_data['date_created'])
				$version_data['date_created'] = time(); //a new one
			$database->insert('lg_scenario_versions',$version_data);
		}
	
		$this->set_leagues($data['id'], $leagues);			
		
		//merge with others:
		if(is_array($scenarios_merge))
		{
			foreach($scenarios_merge AS $scenario_id)
			{
				$scenario = new scenario();
				$scenario->load_data($scenario_id);
				
				//move versions:
				$database->query("UPDATE lg_scenario_versions 
					SET scenario_id = '".$database->escape($data['id'])."'
					WHERE scenario_id = '".$scenario_id."'");
					
				//change in games:
				$database->query("UPDATE lg_games 
					SET scenario_id = '".$database->escape($data['id'])."'
					WHERE scenario_id = '".$scenario_id."'");	
					
				//change favorite in scores:
				$database->query("UPDATE lg_scores 
					SET favorite_scenario_id = '".$database->escape($data['id'])."'
					WHERE favorite_scenario_id = '".$scenario_id."'");
					
				//add games count and duration:
				$database->query("UPDATE lg_scenarios 
					SET games_count = games_count + '".$database->escape($scenario->data['games_count'])."',
					    duration = duration + '".$database->escape($scenario->data['duration'])."'
					WHERE id = '".$database->escape($data['id'])."'");
					
				//add league_mappings:
				$database->query("INSERT INTO lg_league_scenarios (league_id, scenario_id)
					SELECT league_id, '".$database->escape($data['id'])."' AS scenario_id FROM lg_league_scenarios
					WHERE scenario_id = '".$scenario_id."'");
					
				//delete scenario-data
				$scenario->delete($scenario_id);
			}
		}
		
	}
	
	function set_leagues($scenario_id, $leagues)
	{
		global $database;
		$database->delete_where('lg_league_scenarios',"scenario_id = '".$database->escape($scenario_id)."'");
		if(is_array($leagues))
		{
			foreach($leagues AS $league_id => $league_data)
			{
				if($league_data['checked'])
				{
					$ls_data = array();
					$ls_data['scenario_id'] = $scenario_id;
					$ls_data['league_id'] = $league_id;
					$ls_data['max_player_count'] = $league_data['max_player_count'];
					$database->insert('lg_league_scenarios',$ls_data);
				}
			}
		}
	}

	function operator_toggle_league($user, $league_id)
	{
		global $database;
		
		// Check access
		if(!$user->check_operator_permission("scenario", "league_toggle", $league_id))
			return false;
		
		$where = "league_id = '".$database->escape($league_id)."' AND 
		          scenario_id = '".$database->escape($this->data['id'])."'";
		if($database->exists("SELECT * FROM lg_league_scenarios WHERE $where"))
			$database->delete_where('lg_league_scenarios',$where);
		else
		{
			$ls_data = array();
			$ls_data['scenario_id'] = $this->data['id'];
			$ls_data['league_id'] = $league_id;
			$ls_data['max_player_count'] = 0;
			$database->insert('lg_league_scenarios',$ls_data);
		}
	}
	
	function delete($id)
	{
		global $database;
		$a = $database->get_array("SELECT name_sid FROM lg_scenarios WHERE id = '".$database->escape($id)."'");
		global $language;
		$language->delete_strings($a[0]['name_sid']);
		$database->delete_where('lg_scenario_versions', "scenario_id = '".$database->escape($id)."'");
		$database->delete_where('lg_league_scenarios', "scenario_id = '".$database->escape($id)."'");
		$database->delete('lg_scenarios',$id);
	}
	
	function delete_never_played()
	{
		global $database;
		$a = $database->get_array("SELECT id FROM lg_scenarios AS s WHERE games_count = 0 
			AND (SELECT COUNT(*) FROM lg_games AS g WHERE g.scenario_id = s.id) = 0
			AND autocreated = 'Y' AND (SELECT COUNT(*) FROM lg_league_scenarios WHERE scenario_id = id) = 0");
		if(is_array($a))
		{
			$scenario = new scenario();
			global $message_box;
			foreach($a AS $scenario_id)
			{
				$scenario->delete($scenario_id['id']);
				$message_box->add_info("Scenario ".$scenario_id['id']." deleted");
			}
		}
	}
	
	function is_active()
	{
		if($this->data['active'] = 'Y')
			return TRUE;
		else
			return FALSE;
	}
	
	function get_league_type()
	{
		if('settle' == $this->data['type'])
			return 'settle';
		else
			return 'melee';
	}
	
	function get_max_player_counts($league_ids)
	{
		global $database;
		$a = $database->get_array("SELECT max_player_count FROM lg_league_scenarios 
			JOIN lg_leagues AS l ON league_id = l.id
			WHERE scenario_id = '".$this->data['id']."'
			AND league_id IN(".implode($league_ids,',').")
			ORDER BY l.priority ASC");
		
		$max_player_counts = array();
		foreach($a  AS $max_player_count)
		{
			$max_player_counts[] = $max_player_count['max_player_count'];
		}	
		return $max_player_counts;
	}
	
	function show_add()
	{
		global $language;
		global $smarty;
		$smarty->assign("edit_type","add");
		
		global $database;
		$a = $database->get_array("SELECT * FROM lg_products");
		$smarty->assign("products", $a);
		
		$a = $database->get_array("SELECT l.*, IF(s.string IS NULL, 
				(SELECT IF(COUNT(*)=0 , (SELECT string FROM lg_strings s2 WHERE id = l.name_sid LIMIT 1), string)
				AS string FROM lg_strings s2 WHERE language_id = '".$database->escape($language->get_fallback_language_id())."' AND id = l.name_sid LIMIT 1)
				, s.string) AS name
			FROM lg_leagues AS l
			LEFT JOIN lg_strings s 
			ON s.id = l.name_sid AND s.language_id = '".$language->get_current_language_id()."'
			ORDER BY name");
		$smarty->assign("leagues", $a);		
	}
	
	function show_edit($id)
	{
		global $smarty;
		$smarty->assign("edit_type","edit");
		
		global $database;
		$this->load_data($id);
		
		//get strings:
		global $language;
		$strings = $language->get_strings_by_sid($this->data['name_sid']);
		foreach($strings AS $string)
		{
			$this->data['name'][$string['language_id']] = $string['string']; 
		}
	
		$smarty->assign("scenario",$this->data);
		$smarty->assign("versions",$this->versions);
		
		
		$a = $database->get_array("SELECT sc.*,
			".$language->get_string_with_fallback_sql("sc.name_sid"). " AS name
			FROM lg_scenarios AS sc
			ORDER BY name");
		$smarty->assign("scenarios",$a);
		
		$a = $database->get_array("SELECT * FROM lg_products");
		$smarty->assign("products", $a);
		
		$a = $database->get_array("SELECT 
			l.*, IF(ls.league_id IS NULL,0,1) AS active, ls.max_player_count,
				".$language->get_string_with_fallback_sql("l.name_sid"). " AS name
			FROM lg_leagues AS l
			LEFT JOIN lg_league_scenarios AS ls ON ls.league_id = l.id AND ls.scenario_id = '".$database->escape($id)."'
			ORDER BY name");
		$smarty->assign("leagues", $a);				
	}
	
	function show_list($filter = NULL, $page = 0, $sort = NULL)
	{
		if(!$page)
			$page = 0;
		
		global $database;
		global $language;
		global $smarty;
		global $user;
		
	
		$order = "games_count DESC";
		//sort-defaults:
		$smarty->assign("default_sort_col", "games_count");
		$smarty->assign("default_sort_dir", "desc");
		
		if($sort['dir']!='desc')
			$sort['dir'] = 'asc';
			
		if($sort['col']=='name' || $sort['col']=='active'  || $sort['col']=='author' 
		  || $sort['col']=='games_count' || $sort['col']=='type' || $sort['col']=='autocreated'
		  || $sort['col']=='hash' || $sort['col']=='settle_base_score' || $sort['col']=='settle_time_bonus_score'
		  || $sort['col']=='duration' || $sort['col']=='leagues')
		{
			$order = $sort['col']." ".$sort['dir'];
		}
		
		$where = "1=1 ";	
		
		//do some special stuff for league_id:
		if(is_array($filter['league_id']))
		{
			$league_ids = array();
			foreach($filter['league_id'] AS $league_id)
			{
				$league_ids[] = "'".$database->escape($league_id)."'";
			}
			$where .= " AND EXISTS (SELECT * FROM lg_league_scenarios lsc WHERE lsc.scenario_id = sc.id AND lsc.league_id IN (" . implode(',',$league_ids) . "))";
		}
		
		//make search-query:
		if(is_array($filter['search']) && $filter['search'][0])
		{
			$where .=" AND name_sid IN (SELECT id FROM lg_strings WHERE string LIKE '%".$database->escape($filter['search'][0])."%')";
			unset($filter['search']);
		}
		
		$per_page = 50; //TODO: set in config or somewhere else?	
		$limit_start = intval($page * $per_page);
				
		$valid_filters = array("product_id");
		$table_filter = new table_filter();
		$where .= $table_filter->get_where_clause($filter, $valid_filters);
		
		$select = "sc.*, p.name AS product_name, p.icon AS product_icon, ".$language->get_string_with_fallback_sql("sc.name_sid")." AS name";
		
		$select .=
			", (SELECT GROUP_CONCAT(lsc.league_id) 
				FROM lg_league_scenarios AS lsc
				LEFT JOIN lg_leagues AS l ON lsc.league_id = l.id
					AND l.date_start <= '".time()."' AND l.date_end >= '".time()."'
				WHERE lsc.scenario_id = sc.id) AS leagues";
		
		$a = $database->get_array("SELECT $select
			
			FROM lg_scenarios AS sc
			LEFT JOIN lg_products AS p ON sc.product_id = p.id
			
			WHERE $where
			ORDER BY $order
			LIMIT $limit_start, $per_page");

		decode_octal_array($a, 'name');
		
		// Get league list
		$leagues = $database->get_array("SELECT
				".$language->get_string_with_fallback_sql('l.name_sid')." AS name, l.*
				FROM lg_leagues AS l
				WHERE l.scenario_restriction = 'Y' AND l.date_start <= '".time()."' AND l.date_end >= '".time()."'
				ORDER BY l.date_start DESC");
		
		foreach($a as &$scenario) {
		
			// Replace league list string with data about the mentioned leagues
			$league_ids = split(',', $scenario["leagues"]);
			$league_data = array();
			foreach($leagues as $ld) {
				if(in_array($ld['id'], $league_ids)) {
					$league_data[] = $ld;
				}
			}
			$scenario["leagues"] = $league_data;
		}
		
		$smarty->assign("scenarios",$a);
		$smarty->assign("leagues", $leagues);		
		$smarty->assign("page_start",$limit_start+1);
		$smarty->assign("page_items_count",count($a));
		
		//make array for template-filter-status:
		$filter_text_array = array();
		foreach($leagues AS $ld)
		{
			$filter_text_array['league_id'][$ld['id']] = $ld['name'];
		}
		$smarty->assign("filter_text_array", $filter_text_array);

		$a = $database->get_array("SELECT COUNT(*) AS count
			FROM lg_scenarios AS sc
		 	WHERE $where");
		$smarty->assign("page",$page);
		$smarty->assign("page_count",intval(($a[0]['count']-1) / $per_page)+1);
		$smarty->assign("total_items_count",$a[0]['count']);
		
		$a = $database->get_array("SELECT * FROM lg_products");
		$smarty->assign("products", $a);
	}
	
}


?>