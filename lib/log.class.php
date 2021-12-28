<?php

require_once('table_filter.class.php');


class log
{
	function add_error($string)
	{
		global $debug;
		if($debug)
		{
			echo "</br><b><font color=\"#FF0000\">$string</font></b></br>";
		}
		
		//add to error-log:
		$this->add_to_db('error', $string);

	}
	
	function add_user_error($string)
	{
		//add to error-log:
		$this->add_to_db('user_error', $string);

	}
	
	function add($string)
	{
		//add to log:
		$this->add_to_db('info', $string);

	}
	
	function add_game_info($string,$csid)
	{
		//add to log:
		$this->add_to_db('game_info', $string, $csid);

	}
	function add_game_start($string,$csid)
	{
		//add to log:
		$this->add_to_db('game_start', $string, $csid);

	}
	
	function add_auth_join_info($string,$csid = NULL)
	{
		//add to log:
		$this->add_to_db('auth_join', $string, $csid);

	}
	
	function add_to_db($type, $content, $csid = '')
	{
		global $database;
		$data['type'] = $type;
		$data['date'] = time();
		$data['string'] = $content;
		$data['csid'] = $csid;
		$database->insert('lg_log',$data);
	}
	
	function delete_old_entries()
	{
		global $database;
		$time = time() - 5 * 24 * 60 * 60; // 5 days
		$database->delete_where('lg_log', "(type = 'game_start' OR type = 'auth_join') AND date < $time");
		
		$time = time() - 2 * 30 * 24 * 60 * 60; // delete all older than 2 months
		$database->delete_where('lg_log', "date < $time");
	}
	
	function show_list($filter = NULL, $page = 0, $sort = NULL)
	{
		if(!$page)
			$page = 0;
		
		global $database;

		
		$where = " 1=1 ";	
		
		//make search-query:
		if(is_array($filter['search']) && $filter['search'][0])
		{
			$where .=" AND (string LIKE '%".$database->escape($filter['search'][0])."%'
				OR csid = '".$database->escape($filter['search'][0])."') ";
			unset($filter['search']);
		}				
		
		$valid_filters = array("type");
		$table_filter = new table_filter();
		$where .= $table_filter->get_where_clause($filter, $valid_filters);

		$order = "date DESC";
		
		if($sort['dir']!='desc')
			$sort['dir'] = 'asc';
			
		if($sort['col']=='date' || $sort['col']=='type'  || $sort['col']=='string' 
		  || $sort['col']=='csid')
		{
			$order = $sort['col']." ".$sort['dir'];
		}				
		
			
		$per_page = 100;
		$limit_start = intval($page * $per_page);
		
		$a = $database->get_array("SELECT * FROM lg_log 
			WHERE $where
			ORDER BY $order
			LIMIT $limit_start, $per_page");
			
		global $smarty;
		$smarty->assign("log_data",$a);
		
		
		$a = $database->get_array("SELECT COUNT(*) AS count FROM lg_log WHERE $where");
		$smarty->assign("page",$page);
		$smarty->assign("page_count",intval(($a[0]['count']-1) / $per_page)+1);
	}
}


?>
