<?php


class clan
{

	var $data;
	
	function load_data($id)
	{
		if(0 == $id)
			return false;
		
		global $database;
		$a = $database->get_array("SELECT * FROM lg_clans WHERE id = '".$database->escape($id)."'");
		if(!$a[0])
			return false;
		$this->data = $a[0];
		return true;
	}
	
	
	function create($data, &$user)
	{
		$log = new log();
		global $message_box;
		global $language;
		global $database;
		if(FALSE == $this->check_data($data))
			return FALSE;
		
		if($data['password'] != $data['password2'])
		{
			global $message_box;
			global $language;
			$message_box->add_error($language->s('error_password_repeat'));
			return FALSE;
		}			
		
		$data['tag'] = preg_replace('/[^a-zA-Z0-9]/','',$data['tag']);
		
		//check if clan-name or tag already exists:
		global $database;
		$a = $database->get_array("SELECT id FROM lg_clans 
			WHERE name = '".$database->escape($data['name'])."'
			OR tag = '".$database->escape($data['tag'])."'");
		if($a[0]['id'] > 0)
		{
			$log->add_user_error("team ".$data['name']." could not be renamed: a team with this name or tag already exists");
			$this->error = 'error_clan_already_exists';
			$message_box->add_error($language->s('error_clan_already_exists'));
			return FALSE;
		}	
			
		//check name:
		if(false == $user->check_name($data['name']))
		{
			$log->add_user_error("team ".$data['name']." could not be created: invalid teamname");
			$this->error = 'error_clan_invalid_name';
			$message_box->add_error($language->s('error_clan_invalid_name'));
			return FALSE;
		}
		

	
		$log = new log();
		$log->add("clan created: ".$data['name']);
		$data['date_created'] = time();
		$data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
		unset($data['password2']);
		$data['founder_user_id'] = $user->data['id'];
		$this->data['id'] = $database->insert('lg_clans', $data);
		$this->load_data($this->data['id']);
		
		//add clan-id to user:
		$user->join_clan($this->data['id']);
		
		$this->calculate_clan_scores();
		
		return $this->data['id'];
	}
	
	function calculate_clan_scores()
	{
		global $database;
		$a = $database->get_array("SELECT id FROM lg_leagues WHERE type = 'melee'");
		$league_melee = new league_melee();
		foreach($a AS $melee_league_id)
		{
			$league_melee->load_data($melee_league_id['id']);;
			$league_melee->calculate_clan_score($this->data['id']);
			
			//do this calculation in cronjob (too expensive)
			//$league_melee->calculate_clan_stats($this->data['id']);
			
			$league_melee->calculate_clan_ranks();
		}
		//just set a flag for the cronjob to know what to do...:
		$this->data['cronjob_update_stats'] = 1;
		$this->save(); //save cronjob_update_stats-flag
	}
	
	function check_data($data)
	{
		global $database;
		$log = new log();
		$a = $database->get_array("SELECT id FROM lg_clans 
			WHERE name = '".$database->escape($data['name'])."'
			OR tag = '".$database->escape($data['tag'])."'");
		if($a[0]['id'] > 0)
		{
			$log->add_user_error("clan ".$data['name']." could not be renamed: a clan with this name or tag already exists");
			$this->error = 'error_clan_already_exists';
			return FALSE;
		}
		return TRUE;
	}
	
	function save()
	{
		global $database;
		$database->update('lg_clans',$this->data);
	}
	
	function edit($data)
	{
		$log = new log();
		global $message_box;
		global $language;
		global $database;
	
		if($data['password'])
		{
			if($data['password'] != $data['password2'])
			{
				global $message_box;
				global $language;
				$message_box->add_error($language->s('error_password_repeat'));
				unset($data['password']);
			}
			else
				$data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
		}
		else
			unset($data['password']);
			
		$data['tag'] = preg_replace('/[^a-zA-Z0-9]/','',$data['tag']);			
			
		//check if clan-name or tag already exists:
		global $database;
		$a = $database->get_array("SELECT id FROM lg_clans 
			WHERE (name = '".$database->escape($data['name'])."'
			OR tag = '".$database->escape($data['tag'])."')
				 AND id != '".$database->escape($this->data['id'])."'");
		if($a[0]['id'] > 0)
		{
			$log->add_user_error("team ".$data['name']." could not be renamed: a team with this name or tag already exists");
			$this->error = 'error_clan_already_exists';
			$message_box->add_error($language->s('error_clan_already_exists'));
			return FALSE;
		}	
			
		//check name:
		$dummyuser = new user();
		if(false == $dummyuser->check_name($data['name']))
		{
			$log->add_user_error("team ".$data['name']." could not be created: invalid teamname");
			$this->error = 'error_clan_invalid_name';
			$message_box->add_error($language->s('error_clan_invalid_name'));
			return FALSE;
		}
		
		
		$update_data = array();
		$update_data['id'] = $this->data['id'];
		$update_data['name'] = $data['name'];
		$update_data['link'] = $data['link'];
		$update_data['tag'] = $data['tag'];
		$update_data['description'] = $data['description'];
		$update_data['join_disabled'] = $data['join_disabled'];
		if(isset($data['password']))
			$update_data['password'] = $data['password'];
	
		$database->update('lg_clans',$update_data);
		$this->load_data($this->data['id']);
		return TRUE;
	}
	
	function get_users($id = NULL)
	{
		if($id == NULL)
			$id = $this->data['id'];
		global $database;
		$a = $database->get_array("SELECT id, name FROM lg_users WHERE clan_id = '".$database->escape($id)."'");
		if(is_array($a))
			return $a;
		else
			return FALSE;
	}
	

	
	function delete($id)
	{
		global $database;
		$database->delete('lg_clans',$id);
		$database->query("UPDATE lg_users SET clan_id = NULL WHERE clan_id = '".$database->escape($id)."'");
		
		
		$database->delete_where('lg_clan_scores', "clan_id = '".$database->escape($id)."'");
		
		//global $language;
		//global $message_box;
		//$message_box->add_info("$id ".$language->s('deleted'));
		$log = new log();
		$log->add("clan $id deleted");
	}
	
	function delete_small_clans()
	{
		global $database;
		$min_time = time() - 3 * 24 * 60 * 60;
		//delete clans < 3 members and older 3 days:
		$a = $database->get_array("SELECT DISTINCT c.id FROM lg_clans AS c
			WHERE c.date_created < $min_time AND (SELECT COUNT(id) FROM lg_users AS u WHERE u.clan_id = c.id) < 3");
		if(is_array($a))
		{
			foreach($a AS $clan_id)
			{
				$this->delete($clan_id['id']);
			}
		}
	}
	
	function kick($user_id)
	{
		if($user_id == $this->data['founder_user_id'])
			return false;
			
		global $database;
		$database->query("UPDATE lg_users SET clan_id = NULL 
			WHERE id = '".$database->escape($user_id)."'");
			
		$this->calculate_clan_scores();
		return true;
	}
	
	
	function transfer_founder($user_id)
	{
		$user = new user(); //check if user-id is valid:
		if($user->load_data($user_id))
			$this->data['founder_user_id'] = $user_id;
		$this->save();
	}
	
	
	function check_password($password)
	{
		if($this->data['join_disabled'] == 'Y')
			return false;
		
		if(password_verify($password, $this->data['password']))
			return true;
			
		
			
		global $message_box;
		global $language;
		$message_box->add_error($language->s('error_clan_wrong_password'));
		return false;
	}
	
	function show_add()
	{
		global $smarty;
		$smarty->assign("edit_type","add");
	}
	
	function show_edit()
	{
		global $smarty;
		$smarty->assign("edit_type","edit");
		$smarty->assign("clan",$this->data);
	}	
	
	function show_details($id)
	{
		if(false == $this->load_data($id))
			return;
		global $smarty;
		
		$clan_data = $this->data;
		$clan_data['description'] = htmlspecialchars($clan_data['description']);
		$clan_data['description'] = nl2br($clan_data['description']);
		$clan_data['users'] = $this->get_users();
		$smarty->assign("clan",$clan_data);
		
		
		//leagues:
		global $database;
		global $language;
		$a = $database->get_array("SELECT l.*, sc.rank FROM lg_leagues AS l
			JOIN lg_clan_scores AS sc ON sc.league_id = l.id
			WHERE sc.clan_id = '".$database->escape($id)."'
			ORDER BY l.priority DESC");
		foreach($a AS $key => $data)
		{
			$a[$key]['name'] = $language->get_string_with_fallback($data['name_sid']);
		}
		$smarty->assign("leagues",$a);
	}	
	
	function show_list($filter = NULL, $page = 0, $sort = NULL)
	{
		if(!$page)
			$page = 0;
			
		global $database;
		global $smarty;
		
		$where = " 1=1 ";
		
		//make search-query:
		if(is_array($filter['search']) && $filter['search'][0])
		{
			$where .=" AND (c.name LIKE '%".$database->escape($filter['search'][0])."%'
				OR tag LIKE '%".$database->escape($filter['search'][0])."%') ";
			unset($filter['search']);
		}		
		
		//$valid_filters = array("status","g.type","product_id","p.name");
		$valid_filters = array();
		$table_filter = new table_filter();
		$where .= $table_filter->get_where_clause($filter, $valid_filters);

		$per_page = 50; //TODO: set in config or somewhere else?
		$limit_start = intval($page * $per_page);
		
		$order = "user_count DESC";
		//sort-defaults:
		$smarty->assign("default_sort_col", "user_count");
		$smarty->assign("default_sort_dir", "desc");
		
		if($sort['dir']!='desc')
			$sort['dir'] = 'asc';
			
		if($sort['col']=='date_created' || $sort['col'] == 'user_count' || $sort['col'] == 'description'
		  || $sort['col'] == 'name' || $sort['col'] == 'tag' || $sort['col'] == 'link' || $sort['col'] == 'founder_name')
		{
			$order = $sort['col']." ".$sort['dir'];
		}
		
		$a = $database->get_array("SELECT c.*, u.name AS founder_name,
			(SELECT COUNT(id) FROM lg_users AS u WHERE u.clan_id = c.id) AS user_count
			FROM lg_clans AS c
			JOIN lg_users AS u ON u.id = c.founder_user_id
			WHERE $where
			ORDER BY $order , name ASC
			LIMIT $limit_start, $per_page");
			
		if(is_array($a))
		{
			foreach($a AS $key => $clan)
			{
				$a[$key]['users'] = $this->get_users($clan['id']);
			}
		}
		
		if(is_array($a))
			$smarty->assign("clans",$a);
		$smarty->assign("page_start",$limit_start+1);
		$smarty->assign("page_items_count",count($a));
		
		$a = $database->get_array("SELECT COUNT(*) AS count 			
			FROM lg_clans AS c
			JOIN lg_users AS u ON u.id = c.founder_user_id
			WHERE $where");
		$smarty->assign("page",$page);
		$smarty->assign("page_count",intval(($a[0]['count']-1) / $per_page)+1);
		$smarty->assign("total_items_count",$a[0]['count']);
	}



}
