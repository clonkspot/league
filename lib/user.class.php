<?php

require_once('league.class.php');
require_once('league_melee.class.php');

require_once('clan.class.php');

class user
{
	var $data;
	
	var $error; //last error-id (string)
	
	function user()
	{
		$this->error = NULL; 
	}
	
	function get_error()
	{
		return $this->error;
	}
	
	function load_data($id)
	{
		global $database;
		$a = $database->get_array("SELECT * FROM lg_users WHERE id = '".$database->escape($id)."'");
		if(!$a[0])
			return false;
		$this->data = $a[0];
		return true;
	}	
	
	function save()
	{
		global $database;
		$database->update('lg_users',$this->data);
	}
	
	function get_id()
	{
		return $this->data['id'];
	}

	
	function session_login()
	{
		if($_SESSION['user_id'] && $_SESSION['logged_in'])
		{
			
			$this->load_data($_SESSION['user_id']);
			
			//check ban:
			if($this->is_banned($this->data['cuid']))
				return $this->logout();
			
			$this->date_last_login = time();
			$this->save();
		}
	}
	
	/** user-login. if $new_name is set, $name is a CUID, 
	 * and $password a valid webcode, a new account will be created
	 * @param $name can be username or cuid
	 * @param $new_name new nickname when a new account is created
	 */
	function login($name, $password, $new_name = NULL)
	{	
		global $database;
		global $message_box;
		global $language;
		
		$log = new log();
		
		if(is_numeric($name))
		{
			//$name is cuid:
			$a = $database->get_array("SELECT u.cuid, id, password, IF(cb.cuid IS NULL,0,1) AS is_banned, cb.reason 
				FROM lg_users AS u
				LEFT JOIN lg_cuid_bans AS cb ON cb.cuid = u.cuid AND cb.date_until > ".time()."
				WHERE u.cuid = '".$database->escape($name)."'
				 AND u.is_deleted = 0");
		}
		else
		{
			//$name id username
			$a = $database->get_array("SELECT u.cuid, id, password, IF(cb.cuid IS NULL,0,1) AS is_banned, cb.reason 
				FROM lg_users AS u
				LEFT JOIN lg_cuid_bans AS cb ON cb.cuid = u.cuid AND cb.date_until > ".time()."
				WHERE u.name = '".$database->escape($name)."'
				 AND u.is_deleted = 0");
		}
		
		//check for ban:
		if($a[0]['is_banned'] == 1)
		{
			$log = new log();
			$log->add_user_error("user banned. cuid: ".$a[0]['cuid']);
			$this->error = 'error_user_banned';
		
			$message_box->add_error($language->s('error_user_banned'));
			return FALSE;
		}
		
		
		if($a[0]['id'] > 0 && md5($password) == $a[0]['password'])
		{
			$return = TRUE;
			//$log->add("DEBUG: user found ".print_r($a[0],true));
		}
		else if($a[0]['id'] > 0 && $a[0]['password'] == '')
		{
			//pasword was reset -> check webcode again and set webcode as password, if correct:
			if($this->check_webcode($a[0]['cuid'], $password))
			{
				$database->query("UPDATE lg_users SET password = '".$database->escape(md5($password))."'
					WHERE id = '".$a[0]['id']."'");
				$return = TRUE;
			}
			//$log->add("DEBUG: user found: no password: ".print_r($a[0],true));
		}
		else
		{
			//password was wrong, but account exists:
			//(for example: login with CUID/Webcode, but webcode!=password -> no new account, but failure
			//$message_box->add_error($language->s('error_login_failed')); //sent later on
			//$log->add("DEBUG: user found, but wrong password ".print_r($a[0],true));
			$return = FALSE;
		}
		
		if(TRUE == $return)
		{
			$_SESSION['user_id'] = $a[0]['id'];
			$_SESSION['logged_in'] = true;
			$this->load_data($a[0]['id']);
			
			$this->data['date_last_login'] = time();
			$this->save();
			//$log->add("DEBUG: user logged in = TRUE ".print_r($a[0],true));
			return TRUE;
		}
		else
		{
			//$log->add("DEBUG: user: new account".print_r($a[0],true));
			//auto-create account on first login with WEBCODE
			if($this->check_webcode($name,$password)) //has it's own error handling
			{
				if($new_name && is_numeric($name))
				{
					//$log->add("DEBUG: user new account: new name is numeric, webcode is OK ".print_r($a[0],true));
					if($this->create($new_name, $password, $name))
						return $this->login($name, $password);
				}
		
				$message_box->add_error($language->s('error_login_failed'));
				
			}
				//$log->add("DEBUG: user  new account: something was wrong: $name, $new_name, ".print_r($a[0],true));
			return FALSE;
		}
	}
	
	function cuid_user_exists($cuid)
	{
		global $database;
		$a = $database->get_array("SELECT id
			FROM lg_users AS u
			WHERE u.cuid = '".$database->escape($cuid)."'
			 AND u.is_deleted = 0");		
			 
		if($a[0]['id'] > 0)
			return TRUE;
		return FALSE;	
	}
	
	function is_logged_in()
	{
		return $_SESSION['logged_in'];
	}
	
	function is_banned($cuid)
	{
		global $database;
		//check cuid ban:	
		if($database->exists("SELECT cuid
					FROM lg_cuid_bans AS cb 
					WHERE cb.date_until > ".time()."
					AND cuid = '".$database->escape($cuid)."'"))
			return TRUE;
		else
			return FALSE;
	}
	
	function is_league_only_banned($cuid)
	{
		global $database;
		//check cuid ban:	
		if($database->exists("SELECT cuid
					FROM lg_cuid_bans AS cb 
					WHERE cb.date_until > ".time()."
					AND cuid = '".$database->escape($cuid)."'
					AND is_league_only = 1"))
			return TRUE;
		else
			return FALSE;
	}	
	
	function logout()
	{
		$_SESSION['user_id'] = NULL;
		$_SESSION['logged_in'] = 0;
		unset($_SESSION['user_id']);
		unset($_SESSION['logged_in']);
	}
	
	
	function create($name, $password, $cuid)
	{		
		$log = new log();
		global $message_box;
		global $language; 
		global $database;
		
		//check cuid ban:	
		if($this->is_banned($cuid))
		{
			$this->error = 'error_user_banned';
			$message_box->add_error($language->s('error_user_banned'));
			return FALSE;
		}
		
		
		//next errors shouldn't occur after this: make username valid:
		$name = $this->filter_name($name);
		
		//check if user-name already exists:
		$a = $database->get_array("SELECT id FROM lg_users 
			WHERE name = '".$database->escape($name)."'");
		if($a[0]['id'] > 0)
		{
			$log->add_user_error("user $name could not be created: a user with this name already exists");
			$this->error = 'error_user_already_exists';
			$message_box->add_error($language->s('error_user_already_exists'));
			return FALSE;
		}
		
		//check if cuid already exists:
		if(FALSE == $this->check_account_exists($cuid))
		{
			$log->add_user_error("user $name could not be created: cuid already exists: $cuid");
			$this->error = 'error_user_cuid_already_exists';
			$message_box->add_error($language->s('error_user_cuid_already_exists'));		
			return FALSE;
		}
		
		//check username:
		//forbid numeric usernames (numeric values are interpreted as CUIDs)
		if(false == $this->check_name($name))
		{
			$log->add_user_error("user $name could not be created: invalid username");
			$this->error = 'error_invalid_user_name';
			$message_box->add_error($language->s('error_invalid_user_name'));
			return FALSE;
		}
		
		
		//check password (TODO: check for secure password?)
		if(!$this->check_password($password))
		{
			$log->add_user_error("user $name could not be created: password too short");
			$this->error = 'error_password_too_short';
			return FALSE;
		}
		
		
		$log->add("user created: $name");
		$user = array();
		$user['date_created'] = time();
		$user['date_last_login'] = time();
		$user['name'] = $name;
		$user['password'] = md5($password);
		$user['cuid'] = $cuid;
		$this->data['id'] = $database->insert('lg_users', $user);
		$this->load_data($this->data['id']);
		return $this->data['id'];
	}
	
	function check_account_exists($cuid)
	{
		//check if cuid already exists:
		global $database;
		global $language;
		$a = $database->get_array("SELECT id FROM lg_users 
			WHERE cuid = '".$database->escape($cuid)."'
			 AND is_deleted = 0");
		if($a[0]['id'] > 0) {
			$this->error = 'error_user_cuid_already_exists';
			return FALSE;
		}
		return TRUE;
	}
	
	
	function get_name_by_cuid($cuid)
	{
		global $database;
		$a = $database->get_array("SELECT name FROM lg_users 
			WHERE cuid = '".$database->escape($cuid)."'
			 AND is_deleted = 0");
		if(!$a[0])
			return FALSE;
		return $a[0]['name'];
	}
	
	function check_user_exists($name)
	{
		global $database;
		return $database->exists("SELECT * FROM lg_users
			WHERE name = '".$database->escape($name)."'
			 AND is_deleted = 0");
	}
	
	function edit($data)
	{
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
			else if(!$this->check_password($data['password']))
			{
				$log = new log();
				$log->add_user_error("user ".$this->data['name'].": edit password: new password too short");
				unset($data['password']);
			}
			else
				$data['password'] = md5($data['password']);
		}
		else
			unset($data['password']);
			
		if($data['name'] != $this->data['name'])
		{
			// check timing (don't bother with an error, it shouldn't be accessible anyway)
			if($this->data['date_last_rename'] + 2*30*24*60*60 < time())
			{
				if($this->rename($this->data['id'], $data['name']))
				{
					$rename_successful = TRUE;
				}
			}
		}

		$update_data = array();
		$update_data['id'] = $this->data['id'];
		$update_data['real_name'] = $data['real_name'];
		
		if ($rename_successful &&
		   !in_array($this->data['name'], array_map(trim, preg_split('/,/', $this->data['old_names'])))) {
			if($this->data['old_names'] != '')
				$update_data['old_names'] = $this->data['old_names'] . ", " . $this->data['name'];
			else
				$update_data['old_names'] = $this->data['name'];
		}
		
		$update_data['email'] = $data['email'];
		if(isset($data['password']))
			$update_data['password'] = $data['password'];		
		if($rename_successful)
			$update_data['date_last_rename'] = time();
		
		$database->update('lg_users',$update_data);
		$this->load_data($this->data['id']);
	}
	
	function change_password($new_password)
	{
		if(!$this->check_password($new_password))
		{
			$log = new log();
			$log->add_user_error("user ".$this->data['name'].": edit password: new password too short");
			return false;
		}
		
		$data['id'] = $this->data['id'];
		$data['password'] = md5($new_password);
		global $database;
		$database->update('lg_users',$data);
		return true;
	}
	
	
	function add_rank_and_league_data(&$score_data)
	{
		if(is_array($score_data))
		{
			$score = new score();
			foreach($score_data AS $key => $data)
			{
				$score->load_data($data['user_id'],$data['league_id']);
				$rank_symbol = $score->get_rank_symbol();
				$score_data[$key]['rank_icon'] = $rank_symbol['icon'];
				$score_data[$key]['rank_number'] = $rank_symbol['rank_number'];
				$league = new league();
				$league->load_data($data['league_id']);
				$score_data[$key]['league'] = $league->data;
			}
		}	
		return $score_data;
	}
	
	function show_edit()
	{
		global $smarty;
		global $user;
		
		$smarty->assign("user",$this->data);
		
		$a = $this->get_scores_data($this->data['id'], $user->get_operator_leagues());
		$a = $this->add_rank_and_league_data($a);
		$smarty->assign("scores", $a);
				
		global $database;
		if(!$this->data['clan_id'])
		{
			//get clans:
			$a = $database->get_array("SELECT * FROM lg_clans 
				WHERE join_disabled = 'N'
				ORDER BY name");
			$smarty->assign("clans",$a);
		}
		else
		{
			//get clan:
			$clan = new clan();
			$clan->load_data($this->data['clan_id']);
			$clan_data = $clan->data;
			$clan_data['users'] = $clan->get_users();
			$smarty->assign("clan",$clan_data);
		}
	}
	
	function get_scores_data($user_id, $force_leagues = array())
	{
		global $database;
		global $language;
		
		$league_filter_clause = "WHERE s.user_id IS NOT NULL";
		if(count($force_leagues) > 0)
		{
			$escaped_ids = array();
			foreach($force_leagues as $id)
				$escaped_ids[] = $database->escape($id);
			$league_filter_clause .= " OR l.id IN (".implode(',', $escaped_ids).")";
		}
		
		$league_name = $language->get_string_with_fallback_sql('l.name_sid');
		$a = $database->get_array("SELECT s.*, $league_name AS league_name, l.icon AS league_icon, u.name, l.id AS league_id
			FROM lg_leagues l
			LEFT JOIN lg_scores s ON l.id = s.league_id AND s.user_id = '".$database->escape($user_id)."'
			JOIN lg_users AS u ON u.id = '".$database->escape($user_id)."'
			$league_filter_clause");
		
		if(is_array($a))
			return $a;
		else
			return null;
	}
	
	
	function check_webcode($cuid, $webcode)
	{
		global $message_box;
		global $language;

		$log = new log();

		if(strlen($cuid) != 8 || preg_match("/[^0-9]/", $cuid) || 
			strlen($webcode) != 8 || preg_match("/[^0-9A-Z]/", $webcode))
		{
			$log->add_user_error("invalid cuid and/or webcode format");
			$this->error = 'error_webcode_auth_failed';
			$message_box->add_error($language->s("error_webcode_auth_failed"));
			return FALSE;
		}

		$registered = FALSE;
		$cacheKey = "reg_${cuid}_$webcode";
		$cached = apc_fetch($cacheKey);

		if($cached === 1) $registered = TRUE;
		elseif($cached === 0) $registered = FALSE;
		else // FALSE
		{
			$webcode_hash = md5($webcode);
			//"@" wichtig, da sonst URL in Fehlermeldung/Warning ausgegeben werden könnte
			$query = ""; // Contact RedWolf Design if you would like to perform authenticatd CUID/WebCode checks
			$fd = @fopen("", "rb");
			if(!$fd) //URL konnte nicht erreicht werden
			{
				$log->add_user_error("cuid: $cuid - authentification-script could not be reached");
				$this->error = 'error_webcode_auth_na';
				$message_box->add_error($language->s("error_webcode_auth_na"));
				return FALSE;
			}
			$result = stream_get_contents($fd);
			fclose($fd);
			preg_match_all("/(\w+)=(.*)/", $result, $matches);
			$results = array_combine($matches[1], $matches[2]);
			$registered = $results['success'] && preg_match("/\bce\b|\bcr\b/", $results['products']);
			apc_store($cacheKey, $registered ? 1 : 0, 86400);
		}

		if($registered)
		return TRUE;
		else
		{
			$log->add_user_error("wrong webcode for $cuid");
			$this->error = 'error_webcode_auth_failed';
			$message_box->add_error($language->s("error_webcode_auth_failed"));
			return FALSE;
		}
	}
	
	
	
	function check_password($password)
	{
		//TODO: check other criteria
		if(strlen($password)>=4)
			return TRUE;
			
		global $message_box;
		global $language;
		$this->error = 'error_password_too_short';
		$message_box->add_error($language->s("error_password_too_short"));
		return FALSE;
	}
	
	
	
	/* check if the user is an admin and if he's allowed to access the part/method
	*/
	function check_admin_permission($part, $method, $operator = false)
	{
		if(!$this->data['id'])
			return FALSE;
		if(!$operator && false == $this->data['admin'])
			return FALSE;
		
		global $database;
		$a = $database->get_array("SELECT COUNT(*) AS count FROM lg_admin_permissions
			WHERE user_id = '".$this->data['id']."'
			AND part = '".$database->escape($part)."'
			AND (method = '".$database->escape($method)."' OR method = '')");
			
		if($a[0]['count']>0)
			return TRUE;
			
		//else: admin, but no permission
		return FALSE;
	}
	
	function is_admin()
	{
		return $this->data['admin'];
	}
	
	function is_any_operator()
	{
		return $this->data['admin'] || $this->data['operator'] != '';
	}
	
	function check_operator_permission($part, $method, $leagues)
	{
		if(!$this->data['id'])
			return FALSE;
		if(!is_array($leagues))
			$leagues = array($leagues);
			
		// Must be operator of the given leagues or an admin
		if(!$this->is_admin() && !$this->is_operator($leagues))
			return FALSE;
		return $this->check_admin_permission($part, $method, true);
	}
	
	function get_operator_leagues()
	{
		if($this->is_admin())
		{
			$league = new league();
			$a = $league->get_all_active_leagues();
			$ret = array();
			foreach($a as $lg) $ret[] = $lg['id'];
			return $ret;
		}
		if($this->data['operator'] == '')
			return Array();
		return preg_split('/,/', $this->data['operator']);
	}
	
	function is_operator($leagues)
	{
		if($this->data['operator'] == '')
			return FALSE;			
		if(!is_array($leagues))
			$leagues = array($leagues);
		if(count($leagues) == 0)
			return FALSE;
			
		// Must be operator of /all/ involved leagues to get access
		$op_leagues = $this->get_operator_leagues();
		foreach($leagues as $id) {
			if(!in_array($id, $op_leagues))
				return FALSE;
		}

		return TRUE;
	}

	function get_clan_id()
	{
		return $this->data['clan_id'];
	}
	
	function delete($id)
	{
		global $database;
		//set deleted-flag, delete nothing for now:
		$database->query("UPDATE lg_users SET is_deleted = 1
			WHERE id = '".$database->escape($id)."'");
		
		$database->query("UPDATE lg_scores SET user_is_deleted = 1
			WHERE user_id = '".$database->escape($id)."'");

		$database->query("UPDATE lg_game_players SET user_is_deleted = 1
			WHERE user_id = '".$database->escape($id)."'");		
		
		global $language;
		global $message_box;
		$message_box->add_info("$id ".$language->s('deleted'));
		$log = new log();
		$log->add("user $id ".$language->s('deleted'));
	}
	
	function rename($id, $name)
	{
		global $language;
		global $message_box;
		global $database;
		$log = new log();
		
		// get old user name
		$a = $database->get_array("SELECT name FROM lg_users WHERE id = '".$database->escape($id)."'");
		$old_name = $a[0]['name'];
		
		// trim username
		$name = ltrim(rtrim(preg_replace("/ +/"," ",$name)));
		
		//check if user-name already exists:
		$a = $database->get_array("SELECT id FROM lg_users 
			WHERE name = '".$database->escape($name)."'");
		if($a[0]['id'] > 0)
		{
			$log->add_user_error("user $old_name could not be renamed to $name: a user with this name already exists");
			$this->error = 'error_user_already_exists';
			$message_box->add_error("$old_name ".$language->s('error_rename_same_name').": $name");
			return FALSE;
		}		
		
		//check username:
		//forbid numeric usernames (numeric values are interpreted as CUIDs)
		if(false == $this->check_name($name))
		{
			$log->add_user_error("user $old_name could not be renamed to $name: invalid username");
			$this->error = 'error_invalid_user_name';
			$message_box->add_error($language->s('error_invalid_user_name') .": $name");
			return FALSE;
		}
		
		// TODO: Maybe check whether the player is currently in some game? Players are identified by name there...
		
		//perform rename
		$user = new user();
		$user->load_data($id);
		$old_name = $user->data['name'];
		if($name)
			$user->data['name'] = $name;
		$user->save();

		// clear out caches where the old name might still be present
		$database->query("DELETE FROM ghtml
							USING lg_game_players gp JOIN lg_game_list_html ghtml ON ghtml.game_id = gp.game_id
							WHERE gp.user_id = '".$database->escape($id)."'");
		
		$message_box->add_info("$old_name ".$language->s('renamed').": $name");

		$log->add("user: $old_name (id=$id) ".$language->s('renamed').": $name");
		return TRUE;
	}
	
	//name: username to filter
	//id: id of user to ignore when searching for duplicate names
	function filter_name($name, $id = null)
	{
		//make a valid username:
		
		//filter clan tags:
		$name = preg_replace("/(\[.*\])|(\{.*\})/","",$name);
		
		//filter invalid chars:
		$name = preg_replace("/[^0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏĞÑÒÓÔÕÖÙÚÛÜİßàáâãäåæçèéêëìíîïğñòóôõöøùúûüışÿ\ \.\-\_]*/","",$name);
		
		$name = rtrim($name);
		$name = ltrim($name);
		$name = preg_replace("/ +/"," ",$name);		
		
		//no numeric names:
		if(strlen($name) == 0 || is_numeric($name))
			$name = "x";
		
		while(strlen($name) < 3)
		{
			$name .= rand(0,9);
		}
		
		//check if username is unique:
		global $database;
		while(1)
		{
			$a = $database->get_array("SELECT COUNT(id) AS c FROM lg_users 
				WHERE name = '".$database->escape($name)."'
				AND id <> '".$database->escape($id)."'");
			if($a[0]['c'] == 0)
			{
				break;
			}
			//not unique: add random number:
			$name .= rand(0,9);
		}
		
		return $name;
	}
	
	function check_name($name)
	{
		//make a valid username:
		if(preg_match("/[^0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZËÌÍÎÏåæçèéêëìíîïñòóô\ \.\-\_]+/",$name))
			return false;
			
		//no numeric names:
		if(is_numeric($name))
			return false;
		
		//too short:
		if(strlen($name) < 3)
			return false;
		
		return true;
	}
	
	function reset_password($id)
	{
		global $database;
		$database->query("UPDATE lg_users SET password = '' WHERE id = '".$database->escape($id)."'");
		global $language;
		global $message_box;
		$message_box->add_info("$id: ".$language->s('reset_password_done'));
		$log = new log();
		$log->add("user: $id: ".$language->s('reset_password_done'));
	}
	
	function join_clan($clan_id)
	{
		//password-check done in class 'clan'
		$this->data['clan_id'] = $clan_id;
		
		$clan = new clan();
		$clan->load_data($clan_id);
		$clan->calculate_clan_scores();
		$this->save();
	}
	
	function leave_clan()
	{
		global $database;
		$database->query("UPDATE lg_users SET clan_id = NULL 
			WHERE id= '".$database->escape($this->data['id'])."'");
		$clan = new clan();
		$clan->load_data($this->data['clan_id']);
		$clan->calculate_clan_scores();
		$this->data['clan_id'] = '';
	}
	
	function get_clan_tag()
	{
		global $database;
		$a = $database->get_array("SELECT tag FROM lg_users AS u
			JOIN lg_clans AS c ON u.clan_id = c.id
			WHERE u.id = '".$database->escape($this->data['id'])."'");
		if(is_array($a))
			return $a[0]['tag'];
		else
			return false;
	}
	
	function show_details($id)
	{
		global $smarty;
		global $database;
		global $language;
		global $user;
		
		// get scores
		// Note: Including leagues where the current user is operator in, so scores can be set.
		$league = new league();
		$a = $this->get_scores_data($id, $user->get_operator_leagues());
		$a = $this->add_rank_and_league_data($a);
		$smarty->assign("scores", $a);
		
		// load player data
		$player = new user();
		$player->load_data($id);
		$player_data = $player->data;
		if($player_data['clan_id'])
		{
			$a = $database->get_array("SELECT name, tag FROM lg_clans 
				WHERE id = '".$database->escape($player_data['clan_id'])."'");
		
			$player_data['clan_tag'] = $a[0]['tag'];	
			$player_data['clan_name'] = $a[0]['name'];
		}
		$smarty->assign("user",$player_data);
	}

	function show_list($filter = NULL, $page = 0, $sort = NULL, $show_deleted=false)
	{
		if(!$page)
			$page = 0;
			
		global $database;
		global $smarty;
		
		$where = " 1=1 ";
		
		//make search-query:
		if(is_array($filter['search']) && $filter['search'][0])
		{
			global $login_user;
			$where .=" AND (u.name LIKE '%".$database->escape($filter['search'][0])."%'
				OR real_name LIKE '%".$database->escape($filter['search'][0])."%'";
			if($login_user->is_any_operator())
				$where .= " OR cuid = '".$database->escape($filter['search'][0])."'";
			$where .= ")";
			unset($filter['search']);
		}		
		
		//$valid_filters = array("status","g.type","product_id","p.name");
		$valid_filters = array();
		$table_filter = new table_filter();
		$where .= $table_filter->get_where_clause($filter, $valid_filters);

		$per_page = 50; //TODO: set in config or somewhere else?
		$limit_start = intval($page * $per_page);
		
		$order = "name ASC";
		//sort-defaults:
		$smarty->assign("default_sort_col", "name");
		$smarty->assign("default_sort_dir", "asc");
		
		if($sort['dir']!='desc')
			$sort['dir'] = 'asc';
			
		if($sort['col']=='date_created' || $sort['col']=='date_last_login'  || $sort['col']=='date_last_game' 
		  || $sort['col']=='games_melee_won' || $sort['col']=='games_melee_lost' 
		  || $sort['col']=='game_settle_won' || $sort['col']=='games_settle_lost'
		  || $sort['col'] == 'name' || $sort['col'] == 'real_name' ||  $sort['col'] == 'clan_tag')
		{
			$order = $sort['col']." ".$sort['dir'];
		}
		
		if(false==$show_deleted)
			$where .= " AND u.is_deleted = 0 ";
			
		$a = $database->get_array("SELECT u.*, clan.tag AS clan_tag
			FROM lg_users AS u
			LEFT JOIN lg_clans AS clan ON u.clan_id = clan.id
			WHERE $where 
			ORDER BY $order , name ASC
			LIMIT $limit_start, $per_page");
		
		
		$smarty->assign("users",$a);
		$smarty->assign("page_start",$limit_start+1);
		$smarty->assign("page_items_count",count($a));
		
		$a = $database->get_array("SELECT COUNT(*) AS count 			
			FROM lg_users AS u 
			WHERE $where ");
		$smarty->assign("page",$page);
		$smarty->assign("page_count",intval(($a[0]['count']-1) / $per_page)+1);
		$smarty->assign("total_items_count",$a[0]['count']);	
		
	}
	
	function set_score($user_id, $league_id, $new_score)
	{
		global $user;
		$log = new log();
		
		// Check that user is allowed to do this
		if(!($new_score == 0 && $user_id == $user->get_id()) &&
			!$user->check_operator_permission("score","set", $league_id))
		{
			$log->add_user_error("Permission denied for set_score!");
			return false;
		}
		$log->add($user_id. " " . $league_id);
		
		// Set the score
		$score = new score();
		if(!$score->load_data($user_id, $league_id))
			$score->create($user_id, $league_id);
		$score->data['score'] = $new_score;
		$score->save();
		
		// Load league data
		$league = new league();
		$league->load_data($league_id);
		
		// Get clan, recalculate clan score
		$u = new user();
		$u->load_data($user_id);
		if($u->data['clan_id'])
			$league->calculate_clan_score($u->data['clan_id']);
			
		// Recalculate ranks
		$league->calculate_ranks();
		$league->calculate_clan_ranks();
	
		// Done
		$log->add($user->data['name'].' set score of '.$u->data['name'].' to '.$new_score.
			' in league '.$league_id);
		return true;
	}
	
}

?>
