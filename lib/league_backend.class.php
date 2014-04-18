<?php

include_once('game_reference.class.php');
include_once('game.class.php');
include_once('league.class.php');
include_once('user.class.php');

include_once('debug_counter.class.php');

class league_backend
{

	var $output;

	function recieve_reference($raw_data)
	{
		
		$game_reference = new game_reference();
		$game_reference->parse_ini($raw_data);
		
		switch($game_reference->data['[Request]'][0]['Action']) {
		    case 'Start':
		    {
				$this->start_game($game_reference);
				break;
		    }
		    case 'Update':
		    {
				$this->update_game($game_reference);
				break;
		    }
			case 'End':
		    {
				$this->end_game($game_reference);
				break;
		    }
			case 'Auth':
			{
				$this->auth_user($game_reference);	
				break;
			}
		    case 'Join':
		    {
				$this->join($game_reference);
				break;
		    }
		    case 'ReportDisconnect':
		    {
				$this->report_disconnect($game_reference);
				break;
		    }
			case 'Query':
			default:
			{
				if($_REQUEST['action'] == 'query' && $_REQUEST['product_id'])
					$this->send_game_list($_REQUEST['product_id']);
				else
					$this->send_game_list();
			}
		}
		
		global $profiling_start_time;
		$duration = microtime_float() - $profiling_start_time;
		$debug_counter = new debug_counter();
		$debug_counter->increment('recieve_reference',$duration);
		
		//do game-end-debug-counters in $this->end_game()
		$debug_counter->increment('action_'.strtolower($game_reference->data['[Request]'][0]['Action']),$duration);
	}
	
	function send_version()
	{
		$game_reference = new game_reference();
		$this->add_version_to_reference($game_reference);
		$this->send_response($game_reference);
	}
	
	function get_agent_product()
	{
		// TODO Should probably do this by user agent...
		return "OpenClonk";
	}
	
	function get_version()
	{
		global $database;
		$a = $database->get_array("SELECT version FROM lg_products
			WHERE name = '".$database->escape($this->get_agent_product())."'");
			
		//HACK for 238 -> 239-Update
		$hit = array();
		preg_match('/\[(...)\]/',$_SERVER['HTTP_USER_AGENT'], $hit);
		if($hit[1] > 200 && $hit[1] < 239)
			return '4,9,6,6,239';
		else
			return $a[0]['version'];
	}
	
	function get_motd()
	{
		global $database; global $language;
		$a = $database->get_array("SELECT ".$language->get_string_with_fallback_sql('motd_sid')." AS motd
			FROM lg_products WHERE name = '".$database->escape($this->get_agent_product())."'");
		return $a[0]['motd'];
	}	
	
	function add_version_to_reference(&$game_reference)
	{
		$section = "[".$this->get_agent_product()."]";
		$game_reference->data[$section][0]['Version'] = $this->get_version();
		$game_reference->data[$section][0]['MOTD'] = $this->get_motd();
	}
	
	function get_version_string()
	{
		return "[".$this->get_agent_product()."]\nVersion=".$this->get_version()."\nMOTD=".$this->get_motd();
	}
	
	function send_references($references)
	{
		$game_reference = new game_reference();
		
		global $database;
		$this->add_version_to_reference($game_reference);

		if(count($references) == 1)
		{
			$game_reference->data['[Reference]'] = $references[0]->data['[Reference]'];
		}
		elseif(count($references) > 1)
		{
			for($i=0;$i<count($references);$i++)
			{	
				$game_reference->data['[Reference]'][$i] = $references[$i]->data['[Reference]'][0];
			}
		}
		$this->send_response($game_reference);
	}
	
	function send_response($response_ini)
	{
		$this->response = $response_ini->get_ini();
	}
	
	function start_game(&$game_reference)
	{
		global $language;
	
		//check for ban:
		$user = new user();
		$cuid = remove_quotes($game_reference->data['[Reference]'][0]['[Client]'][0]['CUID']);
		//user is banned AND (he is banned for everything OR its a league-game)
		if($user->is_banned($cuid) 
			&& (FALSE==$user->is_league_only_banned($cuid)||$game_reference->data['[Reference]'][0]['LeagueAddress']))
		{
			$response = new game_reference();
			$log = new log();
			$log->add_game_start("error: ".$language->s('error_user_banned'),0);
			$response->data['[Response]'][0]['Status'] = 'Failure';
			$response->data['[Response]'][0]['Message'] =  $language->get_placeholder('error_user_banned'); 			
			$this->send_response($response);
			return FALSE;
		}
		
		$log = new log();
		$game = new game();
		$game_id = $game->create($game_reference);
		
		$response = new game_reference();
		if($game_id)
		{
			$response->data['[Response]'][0]['Status'] = 'Success';
			$response->data['[Response]'][0]['CSID'] = $game->data['csid'];
			if($game->is_league_game())
			{
				$response->data['[Response]'][0]['League'] = $game->get_league_names_string();
			
				//add steaming-url for all league games:
				
				//if('settle' == $game->data['type'])
				//{
					$response->data['[Response]'][0]['StreamTo'] = 
						remove_quotes($game->reference->data['[Reference]'][0]['LeagueAddress'])
						."?action=stream_record&game_id=".$game->data['id']."&";
				//}
				
				if('settle' == $game->data['type'])
				{	
					//add max players: 
					$response->data['[Response]'][0]['MaxPlayers'] = $game->get_max_player_counts_string();
				}
			}
			else
			{
				if($game_reference->data['[Reference]'][0]['LeagueAddress']) // should be a league-game -> error
					$response->data['[Response]'][0]['Message'] =  $language->get_placeholder($game->get_error()); 
			}
			
			//temporarly disabled because of engine-desync-bug
			//$response->data['[Response]'][0]['Seed'] = $game->reference->data['[Reference]'][0]['Seed'];
		}
		else
		{
			//log error:
			$log->add_game_start("error: ".$language->s($game->get_error()),0);
			$response->data['[Response]'][0]['Status'] = 'Failure';
			$response->data['[Response]'][0]['Message'] =  $language->get_placeholder($game->get_error()); 
		}
		$this->send_response($response);
	}
	
	function update_game(&$game_reference)
	{
		$game = new game();
		$response = new game_reference();
		global $language;
		
		if($game->update($game_reference))
		{
			//success:
			$response->data['[Response]'][0]['Status'] = 'Success';
			if($game->is_league_game())
				$game->insert_simulated_score_data_info_update_response($response->data['[Response]'][0]);
		}
		else
		{
			$response->data['[Response]'][0]['Status'] = 'Failure';
			$response->data['[Response]'][0]['Message'] =  $language->get_placeholder($game->get_error());
		}
		$this->send_response($response);
		
	}
	
	function report_disconnect(&$game_reference)
	{
		$game = new game();
		$game->report_disconnect($game_reference);
		$response = new game_reference();

		//always success:
		$response->data['[Response]'][0]['Status'] = 'Success';
		$this->send_response($response);
	}	
	
	function end_game(&$game_reference)
	{
		$game = new game();
		$response = new game_reference();
		global $language;
		
		if($game->end($game_reference))
		{
			//success:
			$response->data['[Response]'][0]['Status'] = 'Success';
			if($game->is_league_game())
			{
				$game->insert_score_data_into_end_response($response->data['[Response]'][0]);
		
				//increment debug counter, only on success:
				global $profiling_start_time;
				$duration = microtime_float() - $profiling_start_time;
				$debug_counter = new debug_counter();		
				if('melee' == $game->data['type'])
					$debug_counter->increment('action_end_league_melee',$duration);
				else
					$debug_counter->increment('action_end_league_settle',$duration);
			}
		}
		else
		{
			$response->data['[Response]'][0]['Status'] = 'Failure';
			$response->data['[Response]'][0]['Message'] =  $language->get_placeholder($game->get_error()); 
		}
		$this->send_response($response);
	}
	
	function send_game_list($product_string = NULL)
	{
		global $database;
		$references = array();
		
		$where="";
		if($product_string)
			$where = " WHERE product_string = '".$database->escape($product_string)."' ";
		//just send all data:
		$a = $database->get_array("SELECT grc.reference_ini FROM lg_game_reference_cache AS grc
			$where
			ORDER BY date_created DESC");
		
		$string = $this->get_version_string();
		
		if(is_array($a))
		{
			foreach($a AS $ref_data)
			{
				$string.="\n".$ref_data['reference_ini'];
			}
		}
		
		$this->response = $string;
	}
	
	function send_game_reference($game_id)
	{
		global $database;
		$references = array();
		
		//just send all data:
		$a = $database->get_array("SELECT g.*, gf.reference FROM lg_games AS g
			JOIN lg_game_reference AS gf ON g.id = gf.game_id
			WHERE (status = 'created' OR status = 'running' OR status = 'lobby')
			AND id = '".$database->escape($game_id)."'");
		
		$game = new game();
		
		if($a[0])
		{
			$reference = new game_reference();
			$reference->set_serialized_data($a[0]['reference']);
			
			$game->data['id'] = $a[0]['id']; //needed to get the league-names. a bit of a hack, but there is no need to load all the game-data...performance...
			$game->insert_league_names($reference);
			
			$references[] = $reference;
		}
		
		$this->send_references($references);
	}

	
	function auth_user(&$game_reference)
	{
		global $language;	
		
		$success = false;
		$status_register = false;
		
		$auth_user = new user();
		
		$name = remove_quotes($game_reference->data['[Request]'][0]['Account']);
		$new_name = remove_quotes($game_reference->data['[Request]'][0]['NewAccount']);
		$password = remove_quotes($game_reference->data['[Request]'][0]['Password']);
		$new_password = remove_quotes($game_reference->data['[Request]'][0]['NewPassword']);
		
		$response = new game_reference();
		
		if($auth_user->login($name, $password))
		{
			//success:
			$success = true;
		}
		else
		{
			//if CUID is set: try to create a new account
			//check CUID and Password as Webcode
			if(is_numeric($name))
			{
				$cuid = $name;
				if($new_name && TRUE == $auth_user->check_account_exists($cuid) && $auth_user->check_webcode($cuid, $password))
				{
					if($new_password)
						$password = $new_password;
						
					if($auth_user->create($new_name, $password, $cuid))
					{
						$success = true;
					}
					else
					{
						$response->data['[Response]'][0]['Message'] = $language->get_placeholder($auth_user->get_error()); 
					}
				}
				elseif(!$new_name && TRUE == $auth_user->check_account_exists($cuid) && $auth_user->check_webcode($cuid, $password))
				{
					//return Status=Register
					$status_register = true;
				}
				else if($auth_user->get_error() != NULL)
				{
					$response->data['[Response]'][0]['Message'] =  $language->get_placeholder($auth_user->get_error()); 
				}
			}
			else
			{
				if($auth_user->get_error())
					$response->data['[Response]'][0]['Message'] =  $language->get_placeholder($auth_user->get_error());
				else	
					$response->data['[Response]'][0]['Message'] =  $language->get_placeholder('error_login_failed');	
			}
		}
		
		//set new password
		if(true == $success && $new_password)
		{
			if(!$auth_user->change_password($new_password))
			{
				$response->data['[Response]'][0]['Message'] =  $language->get_placeholder($auth_user->get_error());
				$success = false;
			}
		}

		
		if(true == $success)
		{		
			$response->data['[Response]'][0]['Status'] = 'Success';
			
			//create unique AUID:
			global $database;
			while(true)
			{
				//loop until a new id was generated
				$auid = md5(uniqid(rand(),true));
				$a = $database->get_array("SELECT auid FROM lg_game_players
					WHERE auid = '".$database->escape($auid)."'");
				if(!$a[0]['auid'])
					break;
			}
			
			//create unique FBID:
			while(true)
			{
				//loop until a new id was generated
				$fbid = md5(uniqid(rand(),true));
				$a = $database->get_array("SELECT fbid FROM lg_game_players
					WHERE fbid = '".$database->escape($fbid)."'");
				if(!$a[0]['fbid'])
					break;
			}
			
			
			$response->data['[Response]'][0]['AUID'] = $auid;
			$response->data['[Response]'][0]['FBID'] = $fbid;
			
			//add user to players-table (for no specific game for now)
			$game = new game();
			$game->add_auth_player($auth_user->data['id'], $auid, $fbid);
		}
		else
		{
			if($status_register)
				$response->data['[Response]'][0]['Status'] = 'Register';
			else
				$response->data['[Response]'][0]['Status'] = 'Failure';
		}
		
		//send back Account-Name:
		if($auth_user->is_logged_in())
		{
			$response->data['[Response]'][0]['Account'] = $auth_user->data['name'];
			//if($clan_tag = $auth_user->get_clan_tag()) //no need for that one here
			//	$response->data['[Response]'][0]['ClanTag'] = $clan_tag;	
		}
		else if(is_numeric($name) && $account = $auth_user->get_name_by_cuid($name))
			$response->data['[Response]'][0]['Account'] = $account;
 		else if($auth_user->check_user_exists($name))
			$response->data['[Response]'][0]['Account'] = $name;
			
		$this->send_response($response);
	}
	
	
	function join(&$game_reference)
	{
		global $language;
		$response = new game_reference();
		$log = new log();
		$csid = $game_reference->data['[Request]'][0]['CSID'];
		if(!$csid)
		{
			//no csid -> error
			$log->add_error("game: join: no csid found: ".$game_reference->get_ini());
			$response->data['[Response]'][0]['Message'] =  $language->get_placeholder('error_no_csid'); 
			$response->data['[Response]'][0]['Status'] = 'Failure';
			$this->send_response($response);
			return;
		}		
		
		$game = new game();
		$game->load_data_by_csid($csid);
		
		if($game->join_player($game_reference))
		{
			//success:
			$response->data['[Response]'][0]['Status'] = 'Success';
			
			//add league-data:
			//$game->get_league_ids()
			//$response->data['[Response]'][0]['League'] = $game->get_league_names_string(); //done when inserting score-data

			$game->insert_score_data_into_join_response($response->data['[Response]'][0], $game_reference->data['[PlrInfo]'][0]['ID']);
			
			$game_player = new game_player();
			$game_player->load_data($game_reference->data['[PlrInfo]'][0]['ID'], $game->data['id']);
			$response->data['[Response]'][0]['Account'] = $game_player->data['name'];
			if($clan_tag = $game_player->get_clan_tag())
				$response->data['[Response]'][0]['ClanTag'] = $clan_tag;
			
			$log = new log();
			$log->add_auth_join_info("Join: user_id = '".$game_player->data['id']."' - Response: ".$response->get_ini(),$csid);
		}
		else
		{
			//failure:
			$response->data['[Response]'][0]['Status'] = 'Failure';
			$response->data['[Response]'][0]['Message'] =  $language->get_placeholder($game->get_error()); 
		}
		
		$this->send_response($response);
	}
	
	
    function checksum_error()
    {
		$log = new log();        
        $log->add_game_start("checksum error!",0);
		global $language;
        $response = new game_reference();
        $response->data['[Response]'][0]['Status'] = 'Failure';
        $response->data['[Response]'][0]['Message'] =  $language->get_placeholder('error_wrong_checksum');
        
		$this->send_response($response);        
    }
	
	function recieve_record_stream($game_id, $pos, $end, $raw_data)
	{
		global $language;
		
		$response = new game_reference();
		
		$game = new game();
		if(false == $game->load_data($game_id))
		{
			$response->data['[Response]'][0]['Status'] = 'Failure';	
			$response->data['[Response]'][0]['Message'] =  $language->get_placeholder('error_game_not_found');
			$this->send_response($response); 
			return;
		}

		if(false == $game->recieve_record_stream($pos, $end, $raw_data))
		{
			$response->data['[Response]'][0]['Status'] = 'Failure';	
			$response->data['[Response]'][0]['Message'] =  $language->get_placeholder($game->get_error());	
		}
		else
		{
			$response->data['[Response]'][0]['Status'] = 'Success';
		}
		$this->send_response($response); 
	}

	function get_response()
	{
		return $this->response;
	}
}



?>