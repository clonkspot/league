<?php

include_once('league.class.php');
include_once('league_melee.class.php');
include_once('league_settle.class.php');
include_once('league_settle_custom.class.php');

include_once('scenario.class.php');
include_once('log.class.php');
include_once('game_reference.class.php');

include_once('game_team.class.php');

include_once('table_filter.class.php');

include_once('resource.class.php');

include_once('game_list_html_cache.class.php');
include_once('flood_protection.class.php'); //used to limit game-list-search-requests

class game
{
	
	var $data;
	var $reference;
	
	var $error; //last error-id (string)
	
	function game()
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
		$a = $database->get_array("SELECT g.*, gr.reference FROM lg_games AS g
			LEFT JOIN lg_game_reference AS gr ON gr.game_id = g.id
			WHERE id = '".$database->escape($id)."'");
		if(!$a[0])
			return FALSE;
		$this->data = $a[0];
		$this->reference = new game_reference();
		$this->reference->set_serialized_data($a[0]['reference']);
		
		return TRUE;	
	}

	function load_data_by_csid($csid)
	{
		global $database;
		$a = $database->get_array("SELECT g.*, gr.reference FROM lg_games AS g
		 	LEFT JOIN lg_game_reference AS gr ON gr.game_id = g.id
		 	WHERE csid = '".$database->escape($csid)."'");
		if(!$a[0])
			return FALSE;
		$this->data = $a[0];
		$this->reference = new game_reference();
		$this->reference->set_serialized_data($a[0]['reference']);
		
		return TRUE;
	}
	
	
	function create(&$game_reference)
	{
		
		$this->insert_client_address($game_reference);
		
		$this->insert_official_server_flag($game_reference);
		
		$this->check_change_hostname($game_reference);
		
		$this->insert_seed($game_reference);
		
		global $database;

		
		//HACK: exception just for Quit
		if($_SERVER["REMOTE_ADDR"] != '213.239.212.12') //(der grosse quit-server.)
		{
			//check host-ip:
			$max_game_count = 3;
			$min_time = time() - 5 * 60;
			$a = $database->get_array("SELECT COUNT(*) AS c FROM lg_games 
				WHERE host_ip = '".$database->escape($_SERVER['REMOTE_ADDR'])."'
				AND date_created >= $min_time"); 
			if($a[0]['c'] >= $max_game_count)
			{
				$this->error = 'error_too_many_gamestarts';
				$log = new log;
				$log->add_user_error("tried to start a game, but too many games in the last few minutes: host-ip: ".$_SERVER['REMOTE_ADDR']);
				return 0;
			}
		}
		
		//insert game-data to database:
		$game_data = array();
		
		$csid = $this->create_csid();
		
		$game_data['csid'] = $csid;
		//set CSID in game_reference:
		$game_reference->data['[Request]'][0]['CSID'] = $csid;
		
		$game_data['date_created'] = $game_data['date_last_update'] = time();
		$game_data['status'] = 'created';
		
		$game_data['seed'] = $game_reference->data['[Reference]'][0]['Seed'];

		//add the game to all fitting leagues for now
		$game_data['type'] = 'noleague';
		$scenario = new scenario();
		$league = new league();
		$leagues_to_add = array();
		
		$product_id = $this->get_product_id_from_reference($game_reference);
		if(false == $product_id)
		{
			$log = new log();
			$log->add_user_error("reference has invalid product-id. version in reference invalid? reference: ".$game_reference->get_ini());
			$this->error = 'error_invalid_product_id';
			return 0;
		}	
		$game_data['product_id'] = $product_id;
				    
		//is league-game?:
		if($game_reference->data['[Reference]'][0]['LeagueAddress'])
		{
			$leagues_data = NULL;
			if($scenario->load_data_by_hash($game_reference->data['[Reference]'][0]['[Scenario]'][0]['FileCRC'],$game_reference->data['[Reference]'][0]['[Scenario]'][0]['FileSHA'], $scenario->get_filename_from_reference($game_reference)))
				$leagues_data = $league->get_active_leagues_by_scenario($product_id, $scenario->data['id']);
			//scenario found by hash: add to fitting leagues
			if($scenario->is_active() && is_array($leagues_data))
			{
				
				foreach($leagues_data AS $league_data)
				{
					if($scenario->get_league_type() == $league_data['type'])
					{
						$leagues_to_add[] = $league_data['id'];
					}
				}		
				
				if($scenario->get_league_type() == 'settle')
				{
					$resource = new resource();
					if(false == $resource->check($game_reference))
					{
						$leagues_to_add = array(); //remove from all leagues
						$this->error = 'error_wrong_resource_checksum';
					}
					
					
					//enable starting settle-games on official servers only
					global $cfg_settle_on_official_server_only;
					if(true == $cfg_settle_on_official_server_only)
					{
						global $cfg_official_server;
						if(false == in_array($_SERVER["REMOTE_ADDR"],$cfg_official_server))
						{
							$leagues_to_add = array(); //remove from all leagues
							$this->error = 'error_settle_no_official_server';	
						}
					}
					
					//enable starting settle-games with latest engine only
					global $cfg_settle_with_latest_engine_only;
					if(true == $cfg_settle_with_latest_engine_only)
					{
						$reference_version = $this->get_version_from_reference($game_reference);
						$reference_build = $this->get_build_from_reference($game_reference);
						global $database;
						$a = $database->get_array("SELECT version FROM lg_products
							WHERE version LIKE '".$database->escape($reference_version)."%'");
						
						//get build out of version string like 4,9,10,0,317
						$build = explode(',',$a[0]['version']);
						if(count($build)>0)
							$build = $build[count($build)-1];
							
						//product must be found and version/build must be the same
						if(!$a[0]['version'] || $build != $reference_build)
						{
							$leagues_to_add = array(); //remove from all leagues
							$this->error = 'error_settle_old_engine';	
						}
					}
				} 
				
			}
			else //scenario not found: check if there is a unrestriced league, and the scenario a melee or teams.txt are set...
			{
				if($this->is_melee($game_reference))
				{
					$leagues_data = $league->get_active_unrestriced_leagues($product_id);
					if(is_array($leagues_data))
					{
						//if the league is unrestricted, search for it or create a new entry on failure
						if((($scenario->load_unrestricted_data_by_game_reference($game_reference) 
							   && $scenario->add_version_by_reference($game_reference)
							   && $scenario->update_scenario_title($game_reference)) //update_scenario_title is always TRUE but does what it says... 
							|| $scenario->add_by_reference($game_reference, $product_id))
						   && $scenario->is_active())
					    {
							foreach($leagues_data AS $league_data)
							{
								if($scenario->get_league_type() == $league_data['type'])
								{
									$leagues_to_add[] = $league_data['id'];
								}
							}
						}
						else
						{
							//should never occur(?) - at the moment: error in scenario::add_by_reference()
							//only when product-id is invalid. but with an invalid product-id, no open league should be found in the step before
							$this->error = 'error_league_scen_could_not_be_added';
						}
					}
					else
					{
						$this->error = 'error_league_scen_not_found';
					}
				}
				else
				{
					$this->error = 'error_league_scen_not_a_melee';
					// Add reference to scenario list here anyway. Useful for admins to add scenarios to the settlement league.
					// Unplayed scenarios will be purged regularly anyway.
					$scenario->add_by_reference($game_reference, $product_id);
				}
			}
			
			if(count($leagues_to_add))
			{
				$game_data['type'] = $scenario->get_league_type();
			}
			else
			{
				if(""==$this->error)
					$this->error = 'error_league_scen_not_found';
			}
			
		}
		
		$game_data['scenario_title'] = $scenario->clean_scenario_title($game_reference->data['[Reference]'][0]['Title']);
			
		$game_data['scenario_id'] = $scenario->data['id'];
		
		//additional game-flags:
		if($game_reference->data['[Reference]'][0]['PasswordNeeded'] == 'true')
			$game_data['is_password_needed'] = 1;
		if($game_reference->data['[Reference]'][0]['UseFairCrew'] == 'true')
			$game_data['is_fair_crew_strength'] = 1;
		if($game_reference->data['[Reference]'][0]['JoinAllowed'] == 'true')
			$game_data['is_join_allowed'] = 1;
		if($game_reference->data['[Reference]'][0]['OfficialServer'] == 'true')
			$this->data['is_official_server'] = 1;
		else
			$this->data['is_official_server'] = 0;
		
		$game_data['host_ip'] = $_SERVER['REMOTE_ADDR'];
		
		$game_data['icon_number'] = $game_reference->data['[Reference]'][0]['Icon'];
			
		
	
		$g_ref = array();
		$g_ref['reference'] = $game_data['reference'];
		unset($game_data['reference']);
		$game_data['id'] = $database->insert('lg_games',$game_data);
		$g_ref['game_id'] = $game_data['id'];
		$game_data['id'] = $database->insert('lg_game_reference',$g_ref);
		$game_data['reference'] = $g_ref['reference'];
		$this->data = $game_data;
		$this->reference = $game_reference;
		
		//add the game-leagues-mapping after adding the game to the database to have a game-id
		foreach($leagues_to_add AS $league_id)
		{
			$database->insert('lg_game_leagues', array('league_id'=>$league_id, 'game_id'=>$this->data['id']));
		}
		
		
		$log = new log;
		$log->add_game_start("Host-IP: ".$_SERVER['REMOTE_ADDR'],$csid);
		//print_a(array(""=>$game_data['reference']));
		
		//insert reference-data just here after setting the league-names:
		$this->insert_league_names($game_reference);
		$this->data['reference'] = $game_reference->get_serialized_data();
		$this->save();

		global $redis;
		if (isset($redis)) {
		      $redis->publish('league:game:create', $this->data['id']);
		}
		
		//called in save():
		//$this->cache_reference();
		
		return $this->data['id'];
	}
	
	function update(&$game_reference)
	{	
		$this->insert_client_address($game_reference);
		
		$this->insert_official_server_flag($game_reference);
		
		$this->check_change_hostname($game_reference);
		
		$csid = $game_reference->data['[Request]'][0]['CSID'];
		if(!$csid)
		{
			$log = new log();
			$log->add_error("game: update: no csid found: ".$game_reference->get_ini());
			$this->error = 'error_no_csid';
			return FALSE;
		}
		
		if(FALSE == $this->load_data_by_csid($csid))
		{
			$log = new log();
			//$log->add_error("game: update: game with csid $csid not found: ".$game_reference->get_ini());
			$log->add_error("game: update: game with csid $csid not found -IP: ".$_SERVER["REMOTE_ADDR"]);
			$this->error = 'error_game_not_found';
			return FALSE;
		}
		
		if('ended' == $this->data['status'])
		{
			$log = new log();
			$log->add_error("game: update: game with csid $csid already ended.".$game_reference->get_ini());
			$this->error = 'error_game_already_ended';
			return FALSE;
		}		
		
		//melees: check host ip:
		if('melee' == $this->data['type']
			&& $_SERVER["REMOTE_ADDR"] != $this->data['host_ip'])
		{
			$log = new log();
			$log->add_error("game: update: game with csid $csid: wrong host ip: 
				old: ".$this->data['host_ip']." new: ".$_SERVER["REMOTE_ADDR"]." ".$game_reference->get_ini());
			$this->error = 'error_wrong_host_ip';
			return FALSE;
		}
		
		$this->insert_game_id($game_reference);
		
		$this->insert_league_names($game_reference);	
		
		//update game_reference:
		$this->reference->update($game_reference);

		$this->data['reference'] = $this->reference->get_serialized_data();
		$this->data['date_last_update'] = time();
		if(($this->data['status'] != 'running'
		&& $this->reference->data['[Reference]'][0]['State'] != "Lobby"
		&& $this->reference->data['[Reference]'][0]['State'] != "Init")
		&& $this->data['status'] != 'ended')
		{
			$this->data['status'] = 'running';
			$this->data['date_started'] = time();
		}
		elseif($this->reference->data['[Reference]'][0]['State'] == "Lobby")
		{
			$this->data['status'] = 'lobby';
		}
		
		if($this->reference->data['[Reference]'][0]['State'] == "Paused")
			$this->data['is_paused'] = 1;
		else
			$this->data['is_paused'] = 0;
		
		//additional game-flags:
		if($this->reference->data['[Reference]'][0]['PasswordNeeded'] == 'true')
			$this->data['is_password_needed'] = 1;
		else
			$this->data['is_password_needed'] = 0;
		if($this->reference->data['[Reference]'][0]['UseFairCrew'] == 'true')
			$this->data['is_fair_crew_strength'] = 1;
		else
			$this->data['is_fair_crew_strength'] = 0;
		if($this->reference->data['[Reference]'][0]['JoinAllowed'] != 'false')
			$this->data['is_join_allowed'] = 1;
		else
			$this->data['is_join_allowed'] = 0;
		if($this->reference->data['[Reference]'][0]['[Teams]'][0]['TeamDistribution'] == 'RandomInv')
			$this->data['is_randominv_teamdistribution'] = 1;
		else
			$this->data['is_randominv_teamdistribution'] = 0;
		if($this->reference->data['[Reference]'][0]['OfficialServer'] == 'true')
			$this->data['is_official_server'] = 1;
		else
			$this->data['is_official_server'] = 0;
			
			
			
		$this->data['duration'] = $this->reference->data['[Reference]'][0]['Time'];
		
		$this->data['icon_number'] = $this->reference->data['[Reference]'][0]['Icon'];
		
		$this->data['frame'] = $this->reference->data['[Reference]'][0]['Frame'];
			
		//do this for league- and for noleague-games...:
		$this->get_and_update_teams_from_game_reference();
		
		$cache = create_game_list_html_cache();
		$cache->del($this->data['id']);
		$result = $this->save();

		global $redis;
		if (isset($redis)) {
		      $redis->publish('league:game:update', $this->data['id']);
		}

		return $result;
	}
	
	function end(&$game_reference, $timeout = false)
	{
		$log = new log;
		$return_value = TRUE; //return-value
		$csid = $game_reference->data['[Request]'][0]['CSID'];
		
		if($timeout == false)
			if(FALSE == $this->update($game_reference))
				return FALSE; //some error-checks done in update()

		if('ended' == $this->data['status'])
		{
			$log->add_error("game: end: game with csid $csid already ended: ".$game_reference->get_ini());
			$this->error = 'error_game_already_ended';
			return FALSE;
		}
		
		if('created' == $this->data['status'] || 'lobby' == $this->data['status'])
		{
			//just reduced log-size: uncomment if needed
			if($timeout)
				$log->add_game_info("game: end: game with csid $csid did never start -> delete it: ".$game_reference->get_ini(), $csid);
			$this->delete();
			return TRUE;
		}
		
		global $database;
		//delete all players still in active-state: they did Auth and Join, but never really joined the game (no Joined-Flag)
		$database->delete_where('lg_game_players', "status = 'active' AND game_id = '".$this->data['id']."'");	
					
		
		//handle disconnect: at least one player had disconnected-flag -> host crashed
		if($timeout == true)
		{
			global $database;
				//it's a melee and at least one disconnected (-> host timed out, not a crash _every_where)
			if($this->data['type'] == 'melee' && $database->exists("SELECT game_id FROM lg_game_players 
					WHERE game_id = '".$this->data['id']."' AND status = 'disconnected'"))
			{
				//no won-flags are set because of disconnect
				//use lost-states set by Removed-Flags
				//all players still alive should have won:
				$database->query("UPDATE lg_game_players SET status = 'won'
					WHERE game_id = '".$this->data['id']."'
					AND status = 'joined'");
					
				//set all non-host to won
				$database->query("UPDATE lg_game_players
					SET status = 'won'
					WHERE game_id = '".$this->data['id']."'
					AND client_id != 0
					AND (status = 'joined' OR status = 'disconnected')");							
				
				//calc team won/lost including host-players:
				$teams = $this->get_teams(); 
				foreach($teams AS $team)
				{
					$team->calc_winner_looser();
				}					
				
				//set all host-players to lost, 
				$database->query("UPDATE lg_game_players
					SET status = 'lost', is_disconnected=1
					WHERE game_id = '".$this->data['id']."'
					AND client_id = 0");
					
				//insert new team for all host-players:
				$a = $database->get_array("SELECT MAX(team_id) AS team_id 
					FROM lg_game_teams WHERE game_id = '".$this->data['id']."'");
				$host_team_id = $a[0]['team_id']+1;				
				$team_data = array();
				$team_data['name'] = "Host";
				//$team_data['color'] = 
				$team_data['team_id'] = $host_team_id;
				$team_data['game_id'] = $this->data['id'];
				$team = new game_team();
				$team->create($team_data);
				//insert new team for all non-host-players being still alive:
				$disconnected_team_id = $host_team_id+1;			
				$team_data = array();
				$team_data['name'] = "Disconnected";
				//$team_data['color'] = 
				$team_data['team_id'] = $disconnected_team_id;
				$team_data['game_id'] = $this->data['id'];
				$team = new game_team();
				$team->create($team_data);			
					
					
				//move host-players to one team
				$database->query("UPDATE lg_game_players
					SET team_id = $host_team_id
					WHERE game_id = '".$this->data['id']."'
					AND client_id = 0");	
				//move non-host-player to another if their whole team didn't already loose:
				$database->query("UPDATE lg_game_players AS gp
					SET team_id = $disconnected_team_id
					WHERE game_id = '".$this->data['id']."'
					AND client_id != 0
					AND EXISTS(SELECT team_id FROM lg_game_teams AS gt WHERE 
						gt.game_id = '".$this->data['id']."' AND gt.team_id = gp.team_id
						AND gt.team_status = 'won')");
						
				$log->add_game_info("game: timeout: malicious disconnect, doing disconnect evaluation", $csid);
	
			}	
			else
			{
			
				$log->add_game_info("game: timeout: no reports, deleting", $csid);
			
				//timeout, no disconnect -> delete
				$this->delete();
				return;
			}							
		}
		
		//still needed after evaluating Removed-Flag?
		$database->query("UPDATE lg_game_players SET status = 'lost'
			WHERE game_id = '".$this->data['id']."'
			AND status = 'joined'");		
					
				
		$this->data['status'] = 'ended'; 
		$this->data['date_ended'] = time();	
		$this->save(); //save game-data before evaluation (settle-league needs state=ended)

		global $database;
		
		
		//delete empty teams:
		$database->delete_where('lg_game_teams',
			"NOT EXISTS( SELECT * FROM lg_game_players 
			WHERE lg_game_players.team_id = lg_game_teams.team_id
			AND lg_game_players.game_id = lg_game_teams.game_id) AND lg_game_teams.game_id = '".$this->data['id']."'");


			
		//TODO: needed just for melee? is this code ever executed??
		//handle disconnects:
		//check if all non-host-players (client_id!=0) have status==disconnected
		if(false == $timeout && false == $database->exists("SELECT * FROM lg_game_players 
			WHERE game_id = '".$this->data['id']."'
			AND client_id != 0
			AND status != 'disconnected' AND status != 'lost'")
			&& $database->exists("SELECT * FROM lg_game_players 
			WHERE game_id = '".$this->data['id']."'
			AND client_id != 0 AND status = 'disconnected'"))
		{
			$database->query("UPDATE lg_game_players SET status = 'lost', is_disconnected = 1
				WHERE game_id = '".$this->data['id']."' AND client_id = 0");
				
			$database->query("UPDATE lg_game_players SET status = 'won'
				WHERE game_id = '".$this->data['id']."' AND client_id != 0 AND status = 'disconnected'");
		}	
		
		
		//get leagues
		$league_ids = $this->get_league_ids();
		
		//set won/lost-flag:
		$teams = $this->get_teams(); 
		
		foreach($teams AS $team)
		{
			$team->calc_winner_looser();
		}
		if(count($league_ids)) //this is also somehow an implicit check if the game is a league-game
		{
			$scenario = new scenario();
			//adding the scenario is done in game::create() if necessary...
			if($scenario->load_data($this->data['scenario_id']))
			{
				//increment scenario-games-count:
				$scenario->data['games_count']++;
				$scenario->data['duration'] += $this->data['duration'];
				$scenario->save();
				
				foreach($league_ids AS $league_id)
				{
					$league = new league();
					$league->load_data($league_id);
					if($league->data['type'] == 'melee')
						$league = new league_melee();
					elseif ($league->is_custom_scoring()) // custom settle (adventure league)
						$league = new league_settle_custom();
					else //settle
						$league = new league_settle();
					$league->load_data($league_id);
					
					
					$at_least_one_league_without_error = FALSE;
					$league_error = null;
					if($league->evaluate($this, $scenario))
					{
						$at_least_one_league_without_error = TRUE;
					}
					else
						$league_error = $league->get_error();
				}
				if(FALSE == $at_least_one_league_without_error)
				{
					//this case normally should not be used. there should be no evaluation-errors.
					$log = new log();
					$log->add_error("game: (id: ".$this->data['id'].") end: evaluation errors in all leagues (leagues set: league-ids: ".implode(";",$league_ids)."): last error: $league_error");
					$this->error = $league_error;
					//do not exit here...count game as non-league-game...but return FALSE in the end.
					$return_value = FALSE;
					$this->delete_league_data();
				}
			}
			else
			{
				//error: scenario not found...so log error and don't do any league-stuff
				//this should only_ happen if the scenario is deleted while playing...
				$log = new log();
				$log->add_error("game: (id: ".$this->data['id'].") end: scenario (id: ".$this->data['scenario_id'].") not found (was deleted?), but leagues set: league-ids: ".implode(";",$league_ids));
				$this->error = 'error_scenario_not_found';
				//do not exit here...count game as non-league-game...but return FALSE in the end.
				$return_value = FALSE;
				$this->delete_league_data();
			}
				
			
			if(TRUE == $return_value)
				$log->add_game_info("game evaluated",$csid);
		}
		
		//$log->add_game_info("game ended",$csid);
		
		global $redis;
		if (isset($redis)) {
		      $redis->publish('league:game:end', $this->data['id']);
		}
		
		//WARNING: DO NOT USE $this->save(); AFTER evaluation as evaluation might change game-data in the tables
		//(it does for settle-scores for example)
		//if any changes are needed here, reload from database first!
		
		return $return_value;
	}
	
	function report_disconnect(&$game_reference)
	{
		global $database;
		$log = new log();
		
		$csid = $game_reference->data['[Request]'][0]['CSID'];
		if(is_array($game_reference->data['[PlayerInfos]'][0]['[Player]']))
		{
			foreach($game_reference->data['[PlayerInfos]'][0]['[Player]'] AS $player_data)
			{
				if($csid) //CSID set => sent by host
				{
					$database->query("UPDATE lg_game_players 
						JOIN lg_games ON lg_game_players.game_id = lg_games.id
						SET lg_game_players.status = 'lost' 
						WHERE player_id = '".$database->escape($player_data['ID'])."'
						AND lg_games.csid = '".$database->escape($csid)."'
						AND lg_game_players.status != 'disconnected'");
					$log->add_game_info("host: player id: ".$player_data['ID']." disconnected", $csid);
				}
				else //sent by client. expect FBID in [Player]
				{
					//check client-IP: if it changed since Auth, discard report:
					$a = $database->get_array("SELECT ip FROM lg_game_players
						WHERE fbid = '".$database->escape($player_data['FBID'])."'
							AND player_id = '".$database->escape($player_data['ID'])."'");
					if($_SERVER["REMOTE_ADDR"] != $a[0]['ip'])
					{
						$log->add_game_info("client: player id: ".$player_data['ID']." (FBID: ".$player_data['FBID'].") disconnect-report (reason: ".$game_reference->data['[Request]'][0]['Reason'].") invalid: client-ip changed since auth", $csid);
					}
					else
					{
						if($game_reference->data['[Request]'][0]['Reason'] == 'Desync') //desync
						{	
							//delete from database:
							$database->query("DELETE FROM lg_game_players 
								WHERE fbid = '".$database->escape($player_data['FBID'])."'
								AND player_id = '".$database->escape($player_data['ID'])."'");
							$log->add_game_info("client: player id: ".$player_data['ID']." (FBID: ".$player_data['FBID'].") desynced", $csid);
						}
						else //disconnect
						{
							$database->query("UPDATE lg_game_players SET status = 'disconnected' 
								WHERE player_id = '".$database->escape($player_data['ID'])."'
								AND fbid = '".$database->escape($player_data['FBID'])."'
								AND (status = 'joined')");
							$log->add_game_info("client: player id: ".$player_data['ID']." (FBID: ".$player_data['FBID'].") disconnected", $csid);
						}
					}
				}
			}
		}
		else
		{
			$log = new log();
			$log->add_error("recieved ReportDisconnect without player-infos: ".$game_reference->get_ini());
		}
	}
	
	function get_league_ids()
	{
		global $database;
		$a = $database->get_array("SELECT league_id FROM lg_game_leagues
			WHERE game_id = '".$this->data['id']."'");
		$league_ids = array();	
		if(is_array($a))
		{
			foreach($a AS $lids)
			{
				$league_ids[] = $lids['league_id'];
			}	
		}
		return $league_ids;		
	}
	
	function get_league_names()
	{
		global $database;
		global $language;
		$a = $database->get_array("SELECT 
				IF(s.string IS NULL, 
				(SELECT IF(COUNT(*)=0 , (SELECT string FROM lg_strings s2 WHERE id = l.name_sid LIMIT 1), string)
				AS string FROM lg_strings s2 WHERE language_id = '".$database->escape($language->get_fallback_language_id())."' AND id = l.name_sid LIMIT 1)
				, s.string) AS string
			FROM lg_game_leagues gl
			JOIN lg_leagues AS l ON l.id = gl.league_id
			LEFT JOIN lg_strings s 
			ON s.id = l.name_sid AND s.language_id = '".$language->get_current_language_id()."'
			WHERE gl.game_id = '".$this->data['id']."'
			ORDER BY priority ASC");
		$league_names = array();	
		if(is_array($a))
		{
			foreach($a AS $l)
			{
				$league_names[] = $l['string'];
			}	
		}
		return $league_names;		
	}
	
	function save()
	{
		global $database;
		global $redis;
		
		// Reference is saved into seperate table
		$g_ref = array();
		$g_ref['reference'] = $this->data['reference'];
		unset($this->data['reference']);
		
		// Set dependent fields
		$this->data['no_settle_rank'] = ($this->data['settle_rank'] == 0 ? 1 : 0);
		$this->data['date_created_neg'] = -$this->data['date_created'];
		
		$database->update('lg_games',$this->data);
	
		$database->update_where('lg_game_reference',"game_id = '".$database->escape($this->data['id'])."'", $g_ref);

		if (isset($redis)) {
		      // Cache game and reference as JSON in Redis. Games expire after 10 minutes.
		      $redis->pipeline(function($pipe) {
			    $id = $this->data['id'];
			    $pipe->setex("league:game:$id", 600, json_encode($this->data));
			    $pipe->setex("league:game_reference:$id", 600, json_encode($this->reference->data));
			    if ($this->data['status'] != 'ended')
				  $pipe->sadd('league:active_games', $id);
			    else
				  $pipe->srem('league:active_games', $id);
		      });
		}
		
		$this->data['reference'] = $g_ref['reference'];
		$this->cache_reference();
		return true;
	}

	
	function insert_client_address(&$reference)
	{
      if($reference->data['[Reference]'][0]['Address'])
      {
		$reference->data['[Reference]'][0]['Address'] 
			= str_replace(":0.0.0.0:",":".$_SERVER["REMOTE_ADDR"].":",$reference->data['[Reference]'][0]['Address']);
	  }
	}
	
	function insert_seed(&$reference)
	{
		$reference->data['[Reference]'][0]['Seed'] = mt_rand(0,2147483647);
	}
	
	function insert_official_server_flag(&$reference)
	{
	  global $cfg_official_server; 
      if(in_array($_SERVER["REMOTE_ADDR"], $cfg_official_server))
      {
		$reference->data['[Reference]'][0]['OfficialServer']="true";
	  }
	  else
		$reference->data['[Reference]'][0]['OfficialServer']="false";
	}
	
	//hostname clonk.de only with an official IP:
	function check_change_hostname(&$reference)
	{
		global $cfg_official_server; 
		if("clonk.de" ==  strtolower(trim(remove_quotes($reference->data['[Reference]'][0]['[Client]'][0]['Name'])))
			&& false == in_array($_SERVER["REMOTE_ADDR"], $cfg_official_server))
		{
			$reference->data['[Reference]'][0]['[Client]'][0]['Name'] = '"'.$_SERVER["REMOTE_ADDR"].'"';
			
			$log = new log();
			$log->add("Hostname was clonk.de -> changed to: ".$reference->data['[Reference]'][0]['[Client]'][0]['Name']);
		}
	}
	
	function insert_game_id(&$reference)
	{
		$reference->data['[Reference]'][0]['GameId']=$this->data['id'];
	}	

	function insert_league_names(&$reference)
	{
		$league_names = $this->get_league_names_string();
		if($league_names)
			$reference->data['[Reference]'][0]['League'] = $league_names;
	}	
	
	function get_league_names_string()
	{
		$league_names = $this->get_league_names();
		for($i=0;$i<count($league_names);$i++)
		{
			$league_names[$i] = '"'.$league_names[$i].'"';
		}
		return implode(',',$league_names);
	}
	
	
	function insert_simulated_score_data_info_update_response(&$response_array)
	{
		//if in lobby, simulate evaluation with this player as a winner
		//and send back the score:
		if($this->data['status'] != 'lobby')
			return;
		
		global $database;
		global $language;	
		$a = $database->get_array("SELECT player_id FROM lg_game_players
			WHERE game_id = '".$this->data['id']."'
			ORDER BY player_id ASC");
		$i=0;

		if(is_array($a))
		{
			$b = $database->get_array("SELECT 
				IF(s.string IS NULL, 
						(SELECT IF(COUNT(*)=0 , (SELECT string FROM lg_strings s2 WHERE id = l.name_sid LIMIT 1), string)
						AS string FROM lg_strings s2 WHERE language_id = '".$database->escape($language->get_fallback_language_id())."' AND id = l.name_sid LIMIT 1)
						, s.string) AS string, gl.league_id, l.type
		
				FROM lg_game_leagues gl
				JOIN lg_leagues AS l ON l.id = gl.league_id
				
				LEFT JOIN lg_strings s ON s.id = l.name_sid AND s.language_id = '".$language->get_current_language_id()."'
				WHERE gl.game_id = '".$this->data['id']."'
				ORDER BY l.priority ASC");	
			
			if($b[0]['type']=='melee')
				$league = new league_melee();
			elseif($b[0]['type']=='settle')
				$league = new league_settle();

    		foreach($a AS $player_data)
    		{				
				$league_names = array();
				$league_simlulated_scores = array();
				$have_simulated_scores = false;
				
				if(is_array($b))
				{
					foreach($b AS $l)
					{
						$league_names[] = $l['string'];
						$league->load_data($l['league_id']);
						if($league->data['custom_scoring'] != 'Y')
						{
							$have_simulated_scores = true;
							$league_simlulated_scores[] = $league->get_winner_score_and_simulate_evaluation($this,$player_data['player_id']);
						}
						else
							$league_simlulated_scores[] = 0;
					}	
				}

				for($j=0;$j<count($league_names);$j++)
				{
					$league_names[$j] = '"'.$league_names[$j].'"';
				}
				
				$response_array['[PlayerInfos]'][0]['[Player]'][$i]['ID'] = $player_data['player_id'];
				
				if($have_simulated_scores)
					$response_array['[PlayerInfos]'][0]['[Player]'][$i]['ProjectedGain'] = implode(',',$league_simlulated_scores);				
    			$i++;
    		}
			$response_array['League'] = implode(',',$league_names);
		}
	}
	
	
	function insert_score_data_into_join_response(&$response_array, $player_id)
	{
		$a = array(); //use this to prevent overwriting the status...
		$this->insert_score_data_into_response($a, $player_id);
		unset($a['Status']);
		unset($a['GameScore']);
		$response_array = array_merge($response_array, $a);
	}
	
	function insert_score_data_into_end_response(&$response_array)
	{
		global $database;
		$a = $database->get_array("SELECT player_id FROM lg_game_players
			WHERE game_id = '".$this->data['id']."'
			ORDER BY player_id ASC");
		$i=0;
		if(is_array($a))
		{
    		foreach($a AS $player_data)
    		{
    			$player_id = $player_data['player_id'];
    			$response_array['[PlayerInfos]'][0]['[Player]'][$i]['ID'] = $player_id;
    			$this->insert_score_data_into_response($response_array['[PlayerInfos]'][0]['[Player]'][$i], $player_id);
    			if(0==$i)
    				$leagues_string = $response_array['[PlayerInfos]'][0]['[Player]'][$i]['League'];
    			unset($response_array['[PlayerInfos]'][0]['[Player]'][$i]['League']);
    			$i++;
    		}
		}

		$response_array['League'] = $leagues_string;
	}
		
	//used for Join-Response and End-Respone. Filter in other functions which data is really needed.
	function insert_score_data_into_response(&$response_array, $player_id)
	{
		global $database;
		global $language;	
		$a = $database->get_array("SELECT 
				IF(s.string IS NULL, 
						(SELECT IF(COUNT(*)=0 , (SELECT string FROM lg_strings s2 WHERE id = l.name_sid LIMIT 1), string)
						AS string FROM lg_strings s2 WHERE language_id = '".$database->escape($language->get_fallback_language_id())."' AND id = l.name_sid LIMIT 1)
						, s.string) AS string,
		
			sc.score, sc.rank, gp.status, gs.score AS game_score, gs.old_player_score, gs.bonus,
			sc.user_id, sc.league_id,
			sud.data AS scenario_user_data
			FROM lg_game_leagues gl
			JOIN lg_leagues AS l ON l.id = gl.league_id
			
			LEFT JOIN lg_game_players gp ON gp.game_id = gl.game_id
			LEFT JOIN lg_scores sc ON sc.league_id = l.id AND sc.user_id = gp.user_id
			
			LEFT JOIN lg_game_scores gs ON gs.league_id = l.id AND gs.game_id = gl.game_id AND gs.player_id = gp.player_id

			LEFT JOIN lg_scenario_user_data sud ON sud.scenario_id = ".$this->data['scenario_id']." AND sud.user_id = gp.user_id
			
			LEFT JOIN lg_strings s ON s.id = l.name_sid AND s.language_id = '".$language->get_current_language_id()."'
			WHERE gl.game_id = '".$this->data['id']."'
			AND gp.player_id = '".$database->escape($player_id)."'
			ORDER BY l.priority ASC");
			
		$score = new score();
			
		$league_names = array();
		$league_scores = array();
		$league_ranks = array();
		$player_status = array();
		$game_scores = array();
		$game_bonus_scores = array();
		$rank_symbols = array();
		$scenario_user_data = array();
		if(is_array($a))
		{
			foreach($a AS $l)
			{
				$league_names[] = $l['string'];
				$league_scores[] = $l['score'];
				$league_ranks[] = $l['rank'];
				$scenario_user_data[] = $l['scenario_user_data'];
				if("lost"==$l['status'])
					$player_status[] = "Lost";
				else
					$player_status[] = "Won";
					
				if('settle' == $this->data['type'])
				{
					//settle: use the "real gain" = new-score minus old-score
					$game_scores[] = $l['score'] - $l['old_player_score'];
				}
				else
				{
					//meele:
					$game_scores[] = $l['game_score'];
				}
				$game_bonus_scores[] = $l['bonus'];
				
				$score->load_data($l['user_id'],$l['league_id']);
				$rank_symbol = $score->get_rank_symbol();
				$rank_symbols[] = $rank_symbol['rank_number'];
			}	
		}
		for($i=0;$i<count($league_names);$i++)
		{
			$league_names[$i] = '"'.$league_names[$i].'"';
			if(!$league_scores[$i])
				$league_scores[$i] = '0';
			if(!$league_ranks[$i])
				$league_ranks[$i] = '0';
			if(!$rank_symbols[$i])
				$rank_symbols[$i] = '0';
		}
		
		$response_array['League'] = implode(',',$league_names);
		$response_array['Score'] = implode(',',$league_scores);
		$response_array['Rank'] = implode(',',$league_ranks);
		$response_array['Status'] = implode(',',$player_status);
		$response_array['GameScore'] = implode(',',$game_scores);
		$response_array['GameBonus'] = implode(',',$game_bonus_scores);
		$response_array['RankSymbol'] = implode(',',$rank_symbols);
		$response_array['ProgressData'] = implode(',',$scenario_user_data);
	}
	
	
	function delete_timeout_games()
	{
		global $database;
		$date = time() - 10 * 60; //older than 10 min
		$where = "(status = 'running' OR status = 'created' OR status = 'lobby') AND date_last_update < '$date'";
		
		$log = new log;

		$a = $database->get_array("SELECT id, type, csid FROM lg_games WHERE $where");
		
		if(is_array($a))
		{
			foreach($a AS $g)
			{
				$log->add_game_info("game timed out",$g['csid']);
				//delete or evaluate -> decide in end()
				$game = new game();
				$game->load_data($g['id']);
				$game->end($game->reference, true); //handle won/lost- and team-stuff in end()
			}
		}
	}
	
	function delete_league_data()
	{
		global $database;
		$database->delete_where('lg_game_leagues', "game_id = ".$this->data['id']);
		$database->delete_where('lg_game_scores', "game_id = ".$this->data['id']);	
		$this->data['type'] = 'noleague';
		
		//delete record-files if available:
		$this->delete_record_stream();
		$this->save();
		
	}
	
	function delete()
	{
		global $database;
		$this->delete_league_data();
		$database->delete_where('lg_game_players', "game_id = ".$this->data['id']);
		$database->delete_where('lg_game_teams', "game_id = ".$this->data['id']);
		$database->delete_where('lg_game_reference', "game_id = ".$this->data['id']);
		$database->delete_where('lg_game_reference_cache', "game_id = ".$this->data['id']);
		$database->delete('lg_games', $this->data['id']);

		global $redis;
		if (isset($redis)) {
		      $redis->pipeline(function($pipe) {
			    $pipe->srem('league:active_games', $this->data['id']);
			    $pipe->publish('league:game:delete', $this->data['id']);
		      });
		}
		
	}
	
	//player did not joined a game:
	function delete_timeout_auth_players()
	{
		global $database;
		$date = time() - 10*60; //older than 10 min
		$where = "lg_game_players.status = 'auth' AND date_auth < '$date' AND game_id = 0";
		
		//for logging only:
		$log = new log;
		$a = $database->get_array("SELECT lg_users.name FROM lg_game_players
			JOIN lg_users ON lg_game_players.user_id = lg_users.id WHERE $where");
		if(is_array($a))
		{
    		foreach($a AS $gp)
    		{
    			$log->add_game_info("player ".$gp['name']." auth timed out",0);
    		}
		}
		
		$database->delete_where('lg_game_players', $where);
	}
	
	function add_auth_player($user_id, $auid, $fbid)
	{
		global $database;
		
		//check if there is already an old auth -> replace, else add a new one:
		if($database->exists("SELECT * FROM lg_game_players 
			WHERE user_id = '$user_id'
			AND status = 'auth'"))
		{
			$database->query("UPDATE lg_game_players SET
				user_id = '$user_id',
				status = 'auth',
				date_auth = ".time().",
				auid = '$auid',
				fbid = '$fbid',
				ip = '".$database->escape($_SERVER["REMOTE_ADDR"])."'
				WHERE user_id = '$user_id' AND status = 'auth'");
			$log = new log();	
			$log->add_auth_join_info("Auth: Update: user-id=$user_id, auid=$auid");
		}
		else
		{
			$database->query("INSERT INTO lg_game_players SET
				user_id = '$user_id',
				status = 'auth',
				date_auth = ".time().",
				auid = '$auid',
				fbid = '$fbid',
				ip = '".$database->escape($_SERVER["REMOTE_ADDR"])."'");
			$log = new log();	
			$log->add_auth_join_info("Auth: Insert: user-id=$user_id, auid=$auid");
		}
	}
	
	
	function join_player(&$reference)
	{
		$log = new log();
		
		if(!$this->data['id'])
		{
			$log->add_error("game: join: game not found");
			$this->error = 'error_game_not_found';
			return FALSE;
		}

		$auid = remove_quotes($reference->data['[Request]'][0]['AUID']);
		global $database;
		$a = $database->get_array("SELECT * FROM lg_game_players
			WHERE status = 'auth' AND auid = '".$database->escape($auid)."'");
		$game_player = $a[0];
		
		
		
		if(!$game_player['user_id'])
		{
			$log->add_error("game: join: no auth-user with AUID $auid found: ".$reference->get_ini());
			$this->error = 'error_auid_not_found';
			return FALSE;
		}
		
		//check if the game is still open/running
		if('ended' == $this->data['status'])
		{
			$log->add_error("join: game already ended: ".$reference->get_ini());
			$this->error = 'error_game_already_ended';
			return FALSE;
		}
		
		//check if the player already is in this game:
		//TODO: for endless games: allow other status-values, like 'quit', too...
		if($database->exists("SELECT * FROM lg_game_players
			WHERE status != 'auth' AND user_id = '".$game_player['user_id']."'
			AND game_id = '".$this->data['id']."'"))
		{
			$log->add_error("game: join: user ".$game_player['user_id']." already in the game: ".$reference->get_ini());
			$this->error = 'error_user_already_joined';
			return FALSE;	
		}
		
//		//check if the maximum user-count for settle-games is reached...
//done in the engine, not needed here for now.
//		if($this->data['type'] == 'settle')
//		{
//			$a = $database->get_array("SELECT COUNT(user_id) AS count FROM lg_game_players 
//				WHERE game_id = '".$this->data['id']."'");
//
//			if($a[0]['count'] >= TODO: GET_MAX_PLAYERS_PER_LEAGUE_AND_SCENARIO)
//			{
//				$log->add_error("game ".$this->data['id'].": join: maximum user-count reached(4)");
//				$this->error = 'error_max_user_count';
//				return FALSE;	
//			}
//		}
		
		$game_player['game_id'] = $this->data['id'];
		$game_player['status'] = 'active';
		$game_player['player_id'] = $reference->data['[PlrInfo]'][0]['ID'];
		$database->update_where('lg_game_players',"status = 'auth' AND user_id = '".$game_player['user_id']."'", $game_player);
		
		$log = new log();	
		$log->add_auth_join_info("Join: user_id = '".$game_player['user_id']."', data: ".print_r($game_player, TRUE)."",$this->data['csid']);
		
		return TRUE;
	}
	
	
	function delete_old_noleague_games()
	{
		global $database;
		$date = time() - 24*60*60; //old
		$where = "(status = 'ended') 
			AND type = 'noleague' AND date_last_update < '$date'";
			
		$a = $database->get_array("SELECT id FROM lg_games WHERE $where");
		$database->delete_where('lg_games', $where);
		
		//delete player- and team-data:
		if(is_array($a))
		{
			$cache = create_game_list_html_cache();
			foreach($a AS $g)
			{
				$database->delete_where('lg_game_players', "game_id = ".$g['id']);
				$database->delete_where('lg_game_teams', "game_id = ".$g['id']);
				$database->delete_where('lg_game_reference', "game_id = ".$g['id']);
				$database->delete_where('lg_game_reference_cache', "game_id = ".$g['id']);
				$cache->del($g['id']);
			}
		}
	}
	
	function delete_old_references()
	{
		global $database;
		$time = time() - 3 * 30 * 24 * 60 * 60; // delete all older than 3 months
		$database->query("DELETE FROM lg_game_reference
			USING lg_game_reference, lg_games AS g
			WHERE lg_game_reference.game_id = g.id AND g.status = 'ended' AND g.date_ended < $time");
	}
	
	function delete_old_ids()
	{
		global $database;
		
		// delete all older than 3 days (Could also delete immediately, I guess)
		$time = time() - 3 * 24 * 60 * 60;
		
		// Remove CSIDs, AUIDs and FBIDs on old games
		// Saves a /lot/ of space for the lg_game_players, not to mention faster indexes all around.
		$database->query("UPDATE lg_games AS g, lg_game_players AS gp 
			SET g.csid=NULL, gp.auid=NULL, gp.fbid=NULL
			WHERE g.id = gp.game_id and g.status = 'ended' AND g.date_ended < $time");
	}
	
	
	function get_players()
	{
		global $database;
		$a = $database->get_array("SELECT player_id FROM lg_game_players
			WHERE game_id = '".$this->data['id']."'");
		
		$players = array();
		if(is_array($a))
		{
			foreach($a AS $p)
			{
				$player = new game_player();
				$player->load_data($p['player_id'], $this->data['id']);
				$players[] = $player;
			}
		}
		return $players;
	}
	
	function get_teams()
	{
		//get all data just from the database, do not use old data!
		
			
		global $database;
		$a = $database->get_array("SELECT team_id FROM lg_game_teams
			WHERE game_id = '".$this->data['id']."'");
		
		$teams = array();
		if(is_array($a))
		{
			foreach($a AS $t)
			{
				$team = new game_team();
				$team->load_data($t['team_id'], $this->data['id']);
				$teams[] = $team;
			}
		}

		return $teams;
	}
	
	
	//requires data['id'] to be set...
	function get_and_update_players_from_game_reference()
	{
		$players = array();
		//echo "<pre>";print_r($this->reference);echo "</pre>";
		
		
		global $database;
		if($this->data['type'] == 'noleague')
		{ //no league: remove all player-data and set the new/updated data later on...:
			$database->delete_where('lg_game_players', "game_id = '".$this->data['id']."'");
		}
		
		if(is_array($this->reference->data['[Reference]'][0]['[PlayerInfos]'][0]['[Client]']))
		{
		
			foreach($this->reference->data['[Reference]'][0]['[PlayerInfos]'][0]['[Client]'] AS $client)
			{
				
				// Look for associated top-level Client section
				foreach($this->reference->data['[Reference]'][0]['[Client]'] AS $c)
					if($c['ID'] == $client['ID'])
					{
						$client_info = $c;
						break;
					}
					
				//echo "<pre>";print_r($client);echo "</pre>";
				if(is_array($client['[Player]']))
				{
					foreach($client['[Player]'] AS $player)
					{						
						$p = array();
						if(is_array($player))
						{
							foreach($player AS $key => $p_data)
							{
								$p[$key] = remove_quotes($p_data);
							}
						}
						
						$flags = explode("|",$p['Flags']);
						
						$player = new game_player();
						if($this->data['type'] == 'noleague')
						{ //is not a league-game: create all players-data
							//create:
							$player_status = 'active';
							if($this->reference->data['[Reference]'][0]['State'] == "Running" && in_array("Joined",$flags))
								$player_status = 'joined';
									
								
							//check for duplicate key, log that:
							if($database->exists("SELECT player_id FROM lg_game_players
								WHERE player_id = '".$database->escape($p['ID'])."' AND	game_id = '".$this->data['id']."'"))
							{
								$log = new log();
								$log->add_game_info("duplicate player-ID: ".$p['ID']." - reference: ".$this->reference->get_ini(),$this->data['csid']);
							}
							else
							{
								//everything ok, insert player
								$database->query("INSERT INTO lg_game_players SET
									player_id = '".$database->escape($p['ID'])."',
									game_id = '".$this->data['id']."',
									status = 'active'");
							}
							
						}

						if($player->load_data($p['ID'],$this->data['id']))
						{ //is a league-game:
							
							//if game-Status is running and the State is not Joined, remove:
							//if($this->reference->data['[Reference]'][0]['State'] == "Running"
							//	&& false == in_array("Joined",$flags))
							//{
								
								//at the moment, don't delete the players because updates are sent without flag-information?!
								/*global $database;
								//delete from database:
								$database->delete_where('lg_game_players',
									"game_id = '".$this->data['id']."' AND player_id = '".$database->escape($p['ID'])."'");
								$log = new log();
								$log->add_error("player deleted: no Joined-Flag but game is running: ".$p['ID']." (".$p['Name'].") - ref: ".$this->reference->get_ini());
								*/
						//	}
							//else
						//	{
                          
							//if the game did start and the player joined, set his status to joined.
							//delete players still in active (==league-Join-performed, but not in the game) in the end.
							//(Important not to delete them here, because the Game-Join can take a while and there can be Updates without the flag set)
							if($this->reference->data['[Reference]'][0]['State'] == "Running" && in_array("Joined",$flags)
								&& $player->player_data['status']=='active')
								$player->player_data['status'] = 'joined';
						
						
                          //always do this stuff, otherwise, league-players get deleted just after game-start
								//set new stuff:
								if(isset($p['Team']))
									$player->player_data['team_id'] = $p['Team'];
									
								//set status-flags for won/lost etc.:
								if(in_array("Removed",$flags))
									$player->player_data['status'] = 'lost';
								if(in_array("Won",$flags)) //won is dominant
									$player->player_data['status'] = 'won';
									
								if(in_array("VotedOut",$flags))
								{
									global $database;
									//delete from database:
									$database->delete_where('lg_game_players',
									"game_id = '".$this->data['id']."' AND player_id = '".$database->escape($p['ID'])."'");
								}
									
								//TODO: remove(?) when Won-Flag is implemented, this is just for testing:
								//if(in_array("Removed",$flags))
								//	$player->player_data['status'] = 'lost';
								
								$player->player_data['player_id'] = $p['ID'];
								$player->player_data['color'] = $p['Color'];
								$player->player_data['name'] = remove_quotes($p['Name']);
								$player->player_data['performance'] = $p['LeaguePerformance'];
								if(isset($client_info))
									$player->player_data['client_cuid'] = remove_quotes($client_info['CUID']);
								
								$player->player_data['client_id'] = $client['ID'];
								
								$player->save_player_data(); //save data (no user-data changed)
								$players[] = $player;	

								// per-player scenario data
								if(isset($p['LeagueProgressData']))
								{
									require_once('lib/scenario_user_data.class.php');
									$scenario_id = $this->data['scenario_id'];
									$user_id = $player->player_data['user_id'];
									// Only allow alphanumeric and spaces. The league could handle any string, but sending weird stuff back in the reference might confuse the engine
									// (e.g., the data for multiple leagues is sent comma-separated)
									$user_data = preg_replace ("/[^a-zA-Z0-9_ ]/", "", $p['LeagueProgressData']);
									// we don't want to save empty user data
									if (!('' === $user_data))
									{
										$scen_user_data = new scenario_user_data();
										$scen_user_data->set($scenario_id, $user_id, $user_data);
										$scen_user_data->save();
									}
								}
						//	}
						}
						
					}
				}

				unset($client_info);

			}
		}
		//print_a($players); echo "#";
		//if players left the lobby, they are removed from the reference.
		//-> remove them from the database, too.
		if($this->reference->data['[Reference]'][0]['State'] == "Lobby")
		{
			global $database;
			$a = $database->get_array("SELECT player_id, name FROM lg_game_players
				WHERE game_id = '".$this->data['id']."'");
			if(is_array($a))
			{
				foreach($a AS $pid)
				{
					$player_id = $pid['player_id'];
					$found = false;
					for($i=0;$i<count($players);$i++)
					{
						if($players[$i]->player_data['player_id'] == $player_id)
						{
							$found = true;
							break;
						}
					}
					if(false == $found)
					{
						//delete from database:
						$database->delete_where('lg_game_players',
							"game_id = '".$this->data['id']."' AND player_id = '$player_id'");
							$log = new log();
						//$log->add_error("player deleted: player left game (not in reference any more): ".$player_id." (".$pid['name'].") - ".print_r($a,TRUE)." - ".print_r($players,TRUE)."- ref: ".$this->reference->get_ini());
					}
				}
			}
		}

		return $players;
	}
	
	
	function get_and_update_teams_from_game_reference()
	{
	
		//first get current player-data:
		$players = $this->get_and_update_players_from_game_reference();
		//delete all old team-information for that game (because it could change)
		global $database;
		$database->delete_where('lg_game_teams', "game_id = '".$this->data['id']."'");
		

		$teams = array();
		//get team-data if there is any. if there are no teams, create a dummy-team per player
		if($this->reference->data['[Reference]'][0]['[Teams]'][0]['Active']!='false')
		{
			if(is_array($this->reference->data['[Reference]'][0]['[Teams]'][0]['[Team]']))
			{
				foreach($this->reference->data['[Reference]'][0]['[Teams]'][0]['[Team]'] AS $team)
				{
					$t = array();
					foreach($team AS $key => $t_data)
					{
						$t[$key] = remove_quotes($t_data);
					}
					
					//set new stuff:
					$team_data = array();
					$team_data['name'] = $t['Name'];
					$team_data['color'] = $t['Color'];
					$team_data['team_id'] = $t['id']; //TODO: check if this stayes in lowercase forever...
					
					//check if there is at least one player in this team:
					$at_least_one_player = false;
					foreach($players AS $player)
					{
						if($player->player_data['team_id'] == $team_data['team_id'])
						{
							$at_least_one_player = true;
							break;
						}
					}
					if(false == $at_least_one_player)
						continue;
					
					
					$team_data['game_id'] = $this->data['id'];
					$team = new game_team();
					$team->create($team_data);
					$teams[] = $team;
				}
			}
		}
		else //no teams: create dummy-teams:
		{
			//melee: create a team for each player, else: create one team for all players:
			if($this->is_melee())
			{
				$team_id = 0; //just count through the players...:
				foreach($players AS $player)
				{
					$team_id++;
					$team_data = array();
					$team_data['name'] = "Team ".$player->data['name'];
					$team_data['color'] = $player->player_data['color'];
					$team_data['team_id'] = $team_id;
					$team_data['game_id'] = $this->data['id'];
					$team = new game_team();
					$team->create($team_data);
					$teams[] = $team;
					
					//change team-id in player:
					$player->player_data['team_id'] = $team_id;
					$player->save_player_data();
				}
			}
			else
			{
				$team_data = array();
				$team_data['name'] = "Team";
				$team_data['team_id'] = 0;
				$team_data['game_id'] = $this->data['id'];
				$team = new game_team();
				$team->create($team_data);
				$teams[] = $team;
				foreach($players AS $player)
				{
					//change team-id in player:
					$player->player_data['team_id'] = 0;
					$player->save_player_data();
				}
			}
		}
		
		return $teams;
	}
	
	function get_version_from_reference(&$reference = null)
	{
		if($reference===null)
			$reference = &$this->reference;
		return $reference->data['[Reference]'][0]['Version'];
	}
	
	function get_build_from_reference(&$reference = null)
	{
		if($reference===null)
			$reference = &$this->reference;
		return $reference->data['[Reference]'][0]['Build'];
	}	
	
	function get_product_id_from_reference(&$reference = null)
	{
		$version = $this->get_version_from_reference($reference);
		
		if(!$version[0])
			return false; //no version
		
		global $database;
		$a = $database->get_array("SELECT id FROM lg_products
			WHERE version LIKE '".$database->escape($version[0])."%'");
			
		if(!$a[0])
			return false;
		else
			return $a[0]['id'];
	}
	
	function get_product_string_from_reference(&$reference = null)
	{
		$version = $this->get_version_from_reference($reference);
		
		if(!$version[0])
			return false; //no version
		
		global $database;
		$a = $database->get_array("SELECT product_string FROM lg_products
			WHERE version LIKE '".$database->escape($version[0])."%'");
			
		if(!$a[0])
			return false;
		else
			return $a[0]['product_string'];
	}	
	
	
	function get_goals(&$reference = null)
	{
		if($reference === null)
			$reference = $this->reference;
			
		$goals = array();
		$goals_data = explode(";",$reference->data['[Reference]'][0]['Goals']);
		foreach($goals_data AS $gd)
		{
			$goal = explode("=",$gd);
			$goals[] = $goal[0];
		}
		return $goals;
	}
	

	function is_melee(&$game_reference = null)
	{
		if(!$game_reference)
			$game_reference = &$this->reference;
		$goals = $this->get_goals($game_reference);
		if(in_array("MELE",$goals) || in_array("MEL2",$goals) || in_array("Goal_Melee",$goals)
			|| $game_reference->data['[Reference]'][0]['[Teams]'][0]['Active']!='false')
			return true;
		else
			return false;
	}
	
	
	function get_team_data($get_score_data = false)
	{
		global $database;
		$a = $database->get_array("SELECT *
			FROM lg_game_teams t
			WHERE game_id = '".$this->data['id']."'
			ORDER BY team_id ASC");

		$return_array = array();
		if(is_array($a))
		{
			for($i=0;$i<count($a);$i++)
			{
				$return_array[$i] = $a[$i];
				$return_array[$i]['players'] = $this->get_player_data($a[$i]['team_id'],$get_score_data);

				//in noleague-games: remove players with the same name
				if($this->data['type']=='noleague')
				{
					$player_count = count($return_array[$i]['players']);
					for($j=$player_count-1;$j>=0;$j--)
					{
						for($k=$j-1;$k>=0;$k--)
						{
							if($return_array[$i]['players'][$j]['name'] == $return_array[$i]['players'][$k]['name'])
							{
								unset($return_array[$i]['players'][$j]);
								break;
							}
						}
					}
					$return_array[$i]['players'] = array_values($return_array[$i]['players']);
				}
				
				decode_octal_array($return_array[$i]['players'], 'name');
			}
		}
		
		return $return_array;
	}
	
	function get_player_data($team_id, $get_score_data = false)
	{
		global $database;
		$a = $database->get_array("SELECT p.*, IF(u.name IS NULL, p.name, u.name) AS name,
			clan.tag AS clan_tag, reg.id AS reg_uid, reg.name as reg_name
			FROM lg_game_players p
			LEFT JOIN lg_users u ON p.user_id = u.id
			LEFT JOIN lg_clans clan ON u.clan_id = clan.id
			LEFT JOIN lg_users reg ON NOT p.client_cuid = '' AND p.client_cuid = reg.cuid
			WHERE p.game_id = '".$this->data['id']."'
			AND p.team_id = '".$database->escape($team_id)."'
			");
			
		if(is_array($a) && $get_score_data)
		{
			for($i=0;$i<count($a);$i++)
			{
				//get score-data:
				$a[$i]['scores'] = $this->get_game_score_data($a[$i]['player_id']);
			}
		}
			
			
		return $a;
	}
	
	
	function get_game_score_data($player_id)
	{
		global $database;
		global $language;
		$a = $database->get_array("SELECT s.*, s_name.string AS league_name, l.icon AS league_icon
			FROM lg_game_scores s
			JOIN lg_leagues l ON l.id = s.league_id
			JOIN lg_strings s_name ON s_name.id = l.name_sid
			WHERE s.player_id = '".$database->escape($player_id)."'
			AND s.game_id = '".$this->data['id']."'
			AND s_name.language_id = '".$language->get_current_language_id()."'");
		
		//If no game-score, game is still running, get at least the old-score:
		if(!is_array($a))
		{
		$a = $database->get_array("SELECT s.score AS old_player_score, s.league_id, s_name.string AS league_name, l.icon AS league_icon
			FROM lg_scores s 
			JOIN lg_leagues l ON l.id = s.league_id
			JOIN lg_game_players gp ON s.user_id = gp.user_id
			JOIN lg_games g ON g.id = gp.game_id
			JOIN lg_strings s_name ON s_name.id = l.name_sid
			JOIN lg_game_leagues AS gl ON gl.league_id = l.id AND gl.game_id = g.id
			WHERE gp.game_id = '".$this->data['id']."'
			AND gp.player_id = '".$database->escape($player_id)."'
			AND s_name.language_id = '".$language->get_current_language_id()."'");
		}	
			
			
		return $a;
	}

	function get_game_data()
	{
		global $database;
		global $language;
		$a = $database->get_array("SELECT g.*, s.name_sid AS scenario_name_sid, s.type AS scenario_type
			FROM lg_games g
			LEFT JOIN lg_scenarios s ON s.id = g.scenario_id
			WHERE g.id = '".$this->data['id']."'");
		
		if($a[0]['scenario_name_sid'])
		{
			$a[0]['scenario_name'] = $language->get_string_with_fallback($a[0]['scenario_name_sid']);
		}

		return $a[0];
	}
	
	
	function is_league_game()
	{
		if($this->data['type'] != 'noleague')
			return true;
		else
			return false;
	}
	
	function get_leagues($game_id)
	{
		global $language;
		global $database;
		$a = $database->get_array("SELECT l.*, (".$language->get_string_with_fallback_sql("l.name_sid").") AS name
		 FROM lg_game_leagues AS gl
		 JOIN lg_leagues AS l ON l.id = gl.league_id
		 WHERE gl.game_id = '".$database->escape($game_id)."'"
		 /*." ORDER BY priority ASC" - makes MySQL create a temporary table and run this /much/ slower. Should do this in PHP, I guess -- Peter */);
		if(count($a))
		 return $a;
		else return FALSE;
	}
	
	function revoke()
	{
		//undo score-gain for each player and each league (each score-object)
		//set revoked-flag
		//recalc rankings and trends
		global $database;			
		global $login_user;
		
		// already revoked?
		if($this->data['is_revoked']  !=  0) {
			return;
		}
		
		// check permission
		if(!$login_user->check_operator_permission("game", "revoke", $this->get_league_ids())) {
			return;
		}
		
		$log = new log();
		$log->add_game_info("game ".$this->data['id']." revoked by ".$user->data['name'],$this->data['csid']);
		
		if($this->data['type'] == 'melee')
		{
			//revoke scores
			$a = $database->get_array("SELECT gp.user_id, sc.league_id, sc.score, sc.bonus, gp.status
				FROM lg_game_scores AS sc
				JOIN lg_game_players AS gp 
				ON gp.game_id = sc.game_id AND gp.player_id = sc.player_id
				WHERE sc.game_id = '".$database->escape($this->data['id'])."'");

			foreach($a AS $game_score)
			{
				$score = new score();
				$user = new user();
				$user->load_data($game_score['user_id']);
				$score->load_data($game_score['user_id'], $game_score['league_id']);
				//revoke score:
				$score->data['score'] -= $game_score['score'] + $game_score['bonus'];
				$score->data['bonus_account'] += $game_score['bonus'];
				//decrement game-counters and duration:
				if($game_score['status'] == 'won')
				{
					$score->data['games_won']--;
					$user->data['games_melee_won']--;
				}
				else
				{
					$score->data['games_lost']--;
					$user->data['games_melee_lost']--;
				}
				$score->data['duration'] -= $this->data['duration'];
				
				$score->save();
				$user->save();
			}
		}
		
		$this->data['is_revoked'] = 1;
		$this->save();
		
		//recalc ranks:
		$a = $database->get_array("SELECT DISTINCT league_id FROM lg_game_leagues 
		WHERE game_id = '".$database->escape($this->data['id'])."'");
		foreach($a AS $league_id)
		{
			if($this->data['type'] == 'settle')
			{
				//recalculate all scores:
				$league_settle = new league_settle();
				$league_settle->load_data($league_id['league_id']);
				if($league_settle->is_custom_scoring())
				{
					$league_settle = new league_settle_custom();
					$league_settle->load_data($league_id['league_id']);
				}
				$league_settle->recalculate_all_scores();
				$league_settle->calculate_ranks();
			}
			else
			{
				$league = new league();
				$league->load_data($league_id['league_id']);
				$league->calculate_ranks();
			}
		}

		//TODO remove game-list-row from cache only if the template is changed...
		$cache = create_game_list_html_cache();
		$cache->del($this->data['id']);
	}
	
	
	function cache_reference()
	{
		$ref = $this->reference; //TODO: deep copy?
		unset($ref->data['[Request]']);
		if($ref)
			$ref_string = $ref->get_ini();
		global $database;
		if($this->data['status']=='created' || $this->data['status']=='lobby' || $this->data['status']=='running')
		{
			$product_string = $this->get_product_string_from_reference();
			
			$database->query("INSERT INTO lg_game_reference_cache
				SET game_id = '".$this->data['id']."', 
				reference_ini = '".$database->escape($ref_string)."',
				date_created = '".$this->data['date_created']."',
				product_string = '".$database->escape($product_string)."'
				ON DUPLICATE KEY UPDATE 
				reference_ini = '".$database->escape($ref_string)."',
				date_created = '".$this->data['date_created']."'");
		}
		else
		{
			$database->delete_where('lg_game_reference_cache', "game_id = '".$this->data['id']."'");
		}
	}
		
	/** add a league-game to the database using the admin-interface
	 */
	function add($data, $leagues, $players)
	{
		global $database;
		
	//	print_a($data);
	//	print_a($leagues);
	//	print_a($players);

		
		
		//ata['date_ended'] = time();
		//$data['date_last_update'] = time();
		$data['csid'] = $this->create_csid();
		$data['type'] = 'melee';
		$data['status'] = 'running';
		$data['date_created'] = strtotime($data['date_created']);
		$data['host_ip'] = $_SERVER["REMOTE_ADDR"]; 
		
		
		$data['id'] = $database->insert('lg_games', $data);
		
		
		
		$reference = new game_reference();		
		$reference->data['[Request]'][0]['Action']='End';	
		$reference->data['[Request]'][0]['CSID']=$data['csid'];		
		$reference->data['[Reference]'][0]['Time']=$data['duration'];		
		$reference->data['[Reference]'][0]['Frame']=$data['frame'];

			
		$at_least_one_league = false;	
		for($i=0;$i<count($leagues['id']);$i++)
		{
			$at_least_one_league = true;
			$game_leagues = array();
			$game_leagues['game_id'] = $data['id'];
			$game_leagues['league_id'] = $leagues['id'][$i];
			$database->insert('lg_game_leagues',$game_leagues);
		}
		
		if(false == $at_least_one_league)
		{
			global $message_box;
			global $language;
			$message_box->add_error($language->s('error_no_league_selected'));
			return;
		}		

		$player_id = 1;
		$team_id = 1;
		foreach($players AS $team)
		{
			$at_least_one_player=false;
			foreach($team AS $player)
			{
				if($player['id'])
				{
					$reference->data['[Reference]'][0]['[PlayerInfos]'][0]['[Client]'][0]['[Player]'][$player_id-1]['ID'] = $player_id;
					$reference->data['[Reference]'][0]['[PlayerInfos]'][0]['[Client]'][0]['[Player]'][$player_id-1]['Team'] = $team_id;
					if($player['status']=='won')
						$reference->data['[Reference]'][0]['[PlayerInfos]'][0]['[Client]'][0]['[Player]'][$player_id-1]['Flags'] = "Joined|Won";
					else
						$reference->data['[Reference]'][0]['[PlayerInfos]'][0]['[Client]'][0]['[Player]'][$player_id-1]['Flags'] = "Joined|Removed";
					$reference->data['[Reference]'][0]['[PlayerInfos]'][0]['[Client]'][0]['[Player]'][$player_id-1]['LeaguePerformance'] = $player['performance'];
					
						
					$game_player = array();
					$game_player['game_id'] = $data['id'];
					$game_player['user_id'] = $player['id'];
					$game_player['status'] = 'joined';
					$game_player['team_id'] = $team_id;
					$game_player['player_id'] = $player_id;
					$game_player['performance'] = $player['performance'];
					
					$log->add('game::add. player='.print_r($player,TRUE));
					$database->insert('lg_game_players',$game_player);	
						
					$player_id++;
					$at_least_one_player=true;
				}
			}
			if($at_least_one_player)
			{
				$reference->data['[Reference]'][0]['[Teams]'][0]['[Team]'][$team_id-1]['id'] = $team_id;
				$reference->data['[Reference]'][0]['[Teams]'][0]['[Team]'][$team_id-1]['Name'] = "Team $team_id";
				$team_id++;
			}
		}
		
		$log = new log();
		$log->add_game_info("leauge-game manually created with dummy-reference:\n".$reference->get_ini(),$data['csid']);
		
		$this->end($reference, false);
	}
	
	function create_csid()
	{
		global $database;
		//create unique CSID:
		while(true)
		{
			//loop until a new id was generated
			$csid = md5(uniqid(rand(),true));
			$a = $database->get_array("SELECT id FROM lg_games
				WHERE csid = '".$database->escape($csid)."'");
			if(!$a[0]['id'])
				break;
		}
		
		return $csid;
	}
	
	
	//get the max. player-count per league for the current scenario/game (used for settle-league)
	function get_max_player_counts_string()
	{
		global $database;
		$scenario = new scenario();
		$scenario->load_data($this->data['scenario_id']);
		
		$a = $database->get_array("SELECT league_id FROM lg_game_leagues
			WHERE game_id = '".$this->data['id']."'");
		$league_ids = array();
		foreach($a  AS $league_id)
		{
			$league_ids[] = $league_id['league_id'];
		}
		
		return implode($scenario->get_max_player_counts($league_ids), ',');
	}
	
	//recieve a part of a streamed record, used for settle-league-games for now.
	//saves the record in the records/-directory, using "records/game_{$game_id}.c4b" for naming
	function recieve_record_stream($pos, $end, $raw_data)
	{
		//check for league_game
		if('noleague' == $this->data['type'])
		{
			$this->error = 'error_game_no_league';
			return false;
		}
		
		if($this->data['record_status'] == 'complete')
		{
			$this->error = 'error_game_record_complete';
			return false;
		}		
		if($pos < 0  || $pos > 1024*1024*10)// safety: max file size 10 MB
		{
			$this->error = 'error_game_record_too_large';
			return false;
		}
		
		//melees: check host ip:
		if('melee' == $this->data['type']
			&& $_SERVER["REMOTE_ADDR"] != $this->data['host_ip'])
		{
			$log = new log();
			$log->add_error("game: recieve_record_stream: game with csid ".$this->data['csid'].": wrong host ip: 
				old: ".$this->data['host_ip']." new: ".$_SERVER["REMOTE_ADDR"]);
			$this->error = 'error_wrong_host_ip';
			return FALSE;
		}	
		
		if('none' == $this->data['record_status'])
		{
			//new filename:
			$random_string = substr(md5(uniqid(rand(),true)), 0, 10);  //length:10 should be enough
			$this->data['record_filename'] = "game_".intval($this->data['id'])."_".$random_string.".c4r";
		}
		
		//$log = new log();
		//$log->add("stream: game_id:".$this->data['id']." filename:".$this->data['record_filename']);		
		
		global $record_folder;
		$f = fopen($record_folder.$this->data['record_filename'], "ab+");
		fseek($f, $pos);
		fwrite($f, $raw_data);
		fclose($f);
		
		if('none' == $this->data['record_status'])
		{	
			//it is a new file:
			chmod($record_folder.$this->data['record_filename'],0660); //rw-rw----
		}
		
		if(1 == $end) //that hast been the last chunk
			$this->data['record_status'] = 'complete';
		else
			$this->data['record_status'] = 'incomplete';
		
		$this->save();
			
		return true;
	}
	
	function delete_record_stream()
	{
		if('none' == $this->data['record_status'] || !$this->data['record_filename'])
			return false;
		
		$record_file = "records/".$this->data['record_filename'];
		
		// Try to delete record, if it exists
		if(is_file($record_file)) {
			$res = unlink($record_file);
			
			$log = new log();
			$log->add("stream: deleted for game_id: ".$this->data['id']." filename:".$this->data['record_filename']." result ".$res);	
		}

		// Still exists?
		if(is_file($record_file))
			return false;
		
		$this->data['record_status']='none';
		$this->data['record_filename']='';			
		return true;
	}

	function delete_old_record_streams()
	{
		global $database;
		
		// Find old streams in database
		$time = time();
		$a = $database->get_array(
			"SELECT g.id 
			 FROM lg_games g
			 JOIN lg_game_leagues gl ON g.id = gl.game_id
			 JOIN lg_leagues l ON gl.league_id = l.id
			 WHERE g.record_status <> 'none' AND g.date_ended < $time - 24*60*60*l.stream_retain_time");
			
		foreach($a as $x) {
			$game = new game();
			$game->load_data($x['id']);
			$game->delete_record_stream();
			$game->save();
		}
		
	}
	
	function show_list($filter = NULL, $page = 0, $sort = NULL)
	{
		if(!$page)
			$page = 0;
			
		global $database;
		global $smarty;
		global $language;
		
		$where = " 1=1 ";
		$join = "";
		
		//replace some stuff:
		if(isset($filter['g.status']) && is_array($filter['g.status']) && 
			(FALSE !== $key = array_search('lobby',$filter['g.status'])))
		{
			$filter['g.status'][]='created';
		}
		
		//filter by user-id:
		if(isset($filter['user_name']) && is_array($filter['user_name']))
		{
			$join .=  "
				JOIN lg_game_players gp ON gp.game_id = g.id
		  		JOIN lg_users u ON u.id = gp.user_id AND u.name = '".$database->escape($filter['user_name'][0])."'";
		
			// filtering by user and exactly one settlement league?
			// (PETER: Actually, this /might/ also work for multiple settlement leagues, now wouldn't it? :) )
			if(count($filter['league_id'])==1 
				&& $database->exists("SELECT id FROM lg_leagues
					WHERE id = '".$database->escape($filter['league_id'][0])."'
					AND type = 'settle'"))
					
				// Only list games that are current - as in: are associated with the current player score
				$join .= " JOIN lg_game_scores gs ON gs.game_id = g.id AND gs.player_id = gp.player_id";
				
			unset($filter['user_name']);
		}
		
		//filter by scenario:
		/*if(is_array($filter['scenario_name']))
		{
			$a = $database->get_array("SELECT sc.id FROM lg_scenarios AS sc
				JOIN lg_strings AS s ON s.id = sc.name_sid
				WHERE s.string = '".$database->escape($filter['scenario_name'][0])."'");
			$filter['scenario_id'][0] = $a[0]['id'];
			unset($filter['scenario_name']);
		}	*/	
		unset($filter['scenario_name']); //name just used for displaying string in filter-status-bar
		
		
		//make search-query:
		$search_filter_used = false;

	
		if(isset($filter['search']) && is_array($filter['search']) && $filter['search'][0])
		{
			//use flood_protection to prevent too many searches in a short time (like pressing enter multiple times)
			$flood_protection = new flood_protection();
			$flood_protection->check_exit("gamelist_search",1,2,"search"); //1 search per 2 seconds should be enough
			
			// Do not allow short filter queries
			if(strlen($filter['search'][0]) < 4)
			{
				global $message_box;
				$message_box->add_error($language->s('error_search_term_too_short'));
			}
			else
			{
				// Will use special query later on
				$search_filter_used = true;
				$search_filter_keyword=$filter['search'][0];
				unset($filter['search']);
			}
		}
		
		//do some special stuff for league_id:
		if(isset($filter['league_id']) && is_array($filter['league_id']))
		{
			
			$league_ids = array();
			foreach($filter['league_id'] AS $league_id)
			{
				$league_ids[] = "'".$database->escape($league_id)."'";
			}
			$where_in = implode(',',$league_ids);
			
			// PETER: A few notes about this: Doing a subquery is in this case faster, because we must expect almost all
			//        games to be league games (after all, non-league games drop after a while)
			//        So it's often a lot better to first sort the whole game list using the index and the filter afterwards.
			//        MySql seems to be pretty smart for requests concerning the most relevant first rows, too.
			
			//        On the other hand, for getting the count, the join is better (no sorting, no limited select).
			//        In case you want to squeeze some more time out of it at the expense of making the code
			//        confusing, you could try to implement that :)
			
			//        It's obviously also the other way round for small leagues, but this doesn't matter much.
			
			if(false)
				$join .= " JOIN lg_game_leagues lg ON lg.game_id = g.id AND lg.league_id IN ($where_in)";
			else
				$where .= " AND EXISTS( SELECT league_id FROM lg_game_leagues lg 
							WHERE league_id IN($where_in) AND lg.game_id = g.id) ";
			
			unset($filter['league_id']);
		}
		
		//filter by product:
		if(isset($filter['p.name']) && is_array($filter['p.name']))
		{
			//get product-id
			$a = $database->query("SELECT id FROM lg_products WHERE name = '".$database->escape($filter['p.name'][0])."'");
			// TODO: This doesn't work
			if($a[0])
				$filter['product_id'] = $a[0]['id'];
			unset($filter['p.name']);
		}		
		
		
		$valid_filters = array("g.status","g.type","product_id","p.name","host_ip","scenario_id");
		
		$table_filter = new table_filter();
		$where .= $table_filter->get_where_clause($filter, $valid_filters);
		
		$per_page = 50; //TODO: set in config or somewhere else?
		$limit_start = intval($page * $per_page);
		
		$order = "";
		
		if(!isset($sort['dir']) || $sort['dir']!='desc')
			$sort['dir'] = 'asc';
		
		if(!isset($sort['col']))
		{
		      $sort['col'] = '';
		}
		elseif($sort['col'] == 'settle_rank')
		{
			$order = "no_settle_rank ".$sort['dir'].", ".$sort['col']." ".$sort['dir'];			
		}
		elseif($sort['col']=='date_created' || $sort['col']=='date_last_update'  || $sort['col']=='g.type' 
		  || $sort['col']=='g.status' || $sort['col']=='date_ended' || $sort['col']=='scenario_name' 
		  || $sort['col']=='player_count' || $sort['col'] == 'duration' 
		  || $sort['col'] == 'settle_score')
		{
			$order = $sort['col']." ".$sort['dir'];
		}
		
		global $language;
		
		// Start building SQL clause
		// 1. SELECT
		$sql_select = "SELECT g.*, p.name AS product_name, p.icon AS product_icon, sc.type AS scenario_type, sc.name_sid ";

		
		//fetch player-count only if it is needed
		if($sort['col']=='player_count')
		{
			$sql_select .= ", (SELECT COUNT(*) FROM lg_game_players WHERE game_id = g.id ) AS player_count ";
		}
		
		// Directly select scenario name? Will be done in another super-query otherwise, see below.
		// The reason is that MySQL seems to screw up badly with deciding when to evaluate the
		// sub-query below once the expression gets reasonably complicated. So we hard-code
		// evaluation order in some of the worse cases and have to live with some overhead.
		$direct_scenario_name_lookup = ($join == '' && !$search_filter_used);
		if($sort['col'] == 'scenario_name')
			$direct_scenario_name_lookup = true;
		if($direct_scenario_name_lookup)
		{
			$sql_select .= ", IF(g.type != 'noleague',".
									$language->get_string_with_fallback_sql('sc.name_sid').",".
									"g.scenario_title) AS scenario_name";
		}
		
		// 2. FROM / JOIN
		$sql_from = "";
		if(false == $search_filter_used)
		{
			$sql_from .= "
				FROM lg_games AS g";
		}
		else
		{
			//search-filter:
			// PETER: MySQL applies the "LIKE" filter /after/ joining lg_games if STRAIGHT_JOIN isn't used, resulting in /horrible/ performance.
			$sql_from .= "
				FROM (
					SELECT STRAIGHT_JOIN g.id
			
					FROM lg_scenarios AS sc
					JOIN lg_games g ON g.scenario_id = sc.id
					WHERE (".$language->get_string_with_fallback_sql('sc.name_sid').") LIKE '%".$database->escape($search_filter_keyword)."%'
				
					UNION DISTINCT
				
					SELECT STRAIGHT_JOIN gp.game_id FROM
				
					  lg_users AS u
					  JOIN lg_game_players AS gp ON u.id = gp.user_id
					  WHERE u.name LIKE '%".$database->escape($search_filter_keyword)."%'
				) AS gids
				JOIN lg_games AS g ON gids.id = g.id";
		}
		
		$sql_from .= $join;
		
		// additional joins needed only for actual list query (see below)
		// filters must not use these, while sorts can!
		$sql_from_add = "
			LEFT JOIN lg_scenarios AS sc ON g.scenario_id = sc.id 
			LEFT JOIN lg_products AS p ON g.product_id = p.id";
		
		// 3. WHERE
		$sql_where =" WHERE $where";
		
		// 4. ORDER BY
		$sql_order = "";
		if($order=="") //default
			$sql_order .=" ORDER BY date_created desc";
		elseif($sort['col']=="date_created")
			$sql_order .= "ORDER BY $order";
		elseif($sort['dir']=="asc") // try to use all ascending search orders - a lot faster this way
			$sql_order .=" ORDER BY $order, date_created_neg asc";
		else
			$sql_order .=" ORDER BY $order, date_created desc";
			
		// 5. LIMIT
		$sql_limit =" LIMIT $limit_start, $per_page";
		
		// 6. Wrap the whole query into another query
		if(!$direct_scenario_name_lookup)
		{
			$wrap_front = "SELECT *, IF(sub.type != 'noleague',".
								$language->get_string_with_fallback_sql('sub.name_sid').",".
								"sub.scenario_title) AS scenario_name 
							FROM (";
			$wrap_end = ') AS sub';
		}
		else
		{
			$wrap_front = ''; $wrap_end = '';
		}
		
		// get results and total possible count
		$a = $database->get_array($wrap_front . $sql_select . $sql_from . $sql_from_add . $sql_where . $sql_order . $sql_limit . $wrap_end);
		$a2 = $database->get_array("SELECT COUNT(*) AS count " . $sql_from . $sql_where);

		$cache = create_game_list_html_cache();
		
		if(!is_array($a))
			$a = NULL;
		for($i=0;$i<count($a);$i++)
		{
			$language_id = $language->get_current_language_id();
			$game_id = $a[$i]['id'];

			// Try to get the entries from cache.
 			$a[$i] = array_merge($a[$i], $cache->get($game_id));

			if($a[$i]['game_list_html'] !== NULL && $a[$i]['game_list_html_2'] !== NULL)
				//already saved, continue
				continue;
			
			if($a[$i]['scenario_name'] == NULL or $a[$i]['scenario_name'] == '')
				$a[$i]['scenario_name'] = "** unknown **";
				
			if($a[$i]['type'] != 'noleague')
			{
				$a[$i]['leagues'] = $this->get_leagues($a[$i]['id']);
			}

			$a[$i]['scenario_name'] = decode_octal($a[$i]['scenario_name']);
			
			//get team-data:
			$this->data = $a[$i];
			$a[$i]['teams'] = $this->get_team_data();
			
			//create and save html
			$smarty->assign("l",$language);
			$smarty->assign("game",$a[$i]);
			$a[$i]['game_list_html'] = $smarty->fetch("game_list_row.tpl");
			$a[$i]['game_list_html_2'] = $smarty->fetch("game_list_row_2.tpl");

			$cache->set($game_id, $a[$i]);
		}	
		
		$smarty->assign("games",$a);
		$smarty->assign("page_start",$limit_start+1);
		$smarty->assign("page_items_count",count($a));
		$smarty->assign("page",$page);
		$smarty->assign("page_count",intval(($a2[0]['count']-1) / $per_page)+1);
		$smarty->assign("total_items_count",$a2[0]['count']);		
		
		
		$a = $database->get_array("SELECT * FROM lg_products");
		$smarty->assign("products", $a);
		
		$league = new league();
		$a = $league->get_all_active_leagues();
		$smarty->assign("leagues", $a);

		//make array for template-filter-status:
		$filter_text_array = array();
		foreach($a AS $ld)
		{
			$filter_text_array['league_id'][$ld['id']] = $ld['name'];
		}
		$smarty->assign("filter_text_array", $filter_text_array);
		
		//sort-defaults:
		$smarty->assign("default_sort_col", "date_created");
		$smarty->assign("default_sort_dir", "desc");
	}
	
	function show_details($id)
	{
		$this->load_data($id);
		global $smarty;
		//print_a($this->get_team_data());
		$game_data = $this->get_game_data();
		$game_data['scenario_name'] = decode_octal($game_data['scenario_name']);
		$smarty->assign("game",$game_data);
		
		if(is_object($this->reference))
			$smarty->assign("game_reference",$this->reference->get_ini());
		$smarty->assign("teams",$this->get_team_data(true));
		//$smarty->assign("players",$this->get_game_reference_players());
		$smarty->assign("goals",$this->get_goals());
		$smarty->assign("leagues",$this->get_league_ids());
	}
	
	function show_add()
	{
		//TODO: select product
		global $smarty;
		global $database;
		global $language;
		$a = $database->get_array("SELECT * FROM lg_products ORDER BY name");
		$smarty->assign("products", $a);		
		
		$a = $database->get_array("SELECT l.*, IF(s.string IS NULL, 
				(SELECT IF(COUNT(*)=0 , (SELECT string FROM lg_strings s2 WHERE id = l.name_sid LIMIT 1), string)
				AS string FROM lg_strings s2 WHERE language_id = '".$database->escape($language->get_fallback_language_id())."' AND id = l.name_sid LIMIT 1)
				, s.string) AS name
			FROM lg_leagues AS l
			LEFT JOIN lg_strings s 
			ON s.id = l.name_sid AND s.language_id = '".$language->get_current_language_id()."'
			ORDER BY name");
		$smarty->assign("leagues",$a);
		
		$a = $database->get_array("SELECT * FROM lg_users WHERE is_deleted = 0 ORDER BY name");
		$smarty->assign("users", $a);
				
		
		
	}
	
}



?>
