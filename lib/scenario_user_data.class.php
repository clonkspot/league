<?php

include_once('user.class.php');
include_once('scenario.class.php');

// user-specific data stored per scenario
// e.g. achievements, adventure scenario progress
class scenario_user_data
{
	var $scenario_id;
	var $user_id;
	var $data;
	
	function __construct()
	{
		$this->scenario_id = 0;
		$this->user_id = 0;
		$this->data = NULL;
	}
	
	function load_data($scenario_id, $user_id)
	{
		// store info
		$this->scenario_id = $scenario_id;
		$this->user_id = $user_id;
		$this->data = NULL;
		//load data from database
		global $database;
		$a = $database->get_array("SELECT data FROM lg_scenario_user_data
			WHERE scenario_id = '".$database->escape($scenario_id)."'
			AND user_id = '".$database->escape($user_id)."'");
		// nothing stored in the database. that's ok.
		if(!$a[0]) return false;
		$this->data = $a[0]['data'];
		return true;
	}
	
	function get_data() { return $this->data; }
	
	function set($scenario_id, $user_id, $data)
	{
		$this->scenario_id = $scenario_id;
		$this->user_id = $user_id;
		$this->data = $data;
		return true;
	}
	
	function save()
	{
		global $database;
		$update_data = array();
		$update_data['scenario_id'] = $this->scenario_id;
		$update_data['user_id'] = $this->user_id;
		$update_data['data'] = $this->data;
		$database->insert_update('lg_scenario_user_data', $update_data);
		return true;
	}


}



?>
