<?php


class cuid_ban
{

	var $data;
	
	function load_data($cuid)
	{
		global $database;
		$a = $database->get_array("SELECT * FROM lg_cuid_bans WHERE cuid = '".$database->escape($cuid)."'");
		
		if(!$a[0])
			return false;
		$this->data = $a[0];
		return true;
	}
	
	function add($data)
	{
		global $database;

		if($data['date_until'] != "")
			$data['date_until'] = strtotime($data['date_until']);
		else
			$data['date_until'] = 1924902000; //somewhen 2030...should be long enough
		$data['date_created'] = time();
		$database->insert_update('lg_cuid_bans', $data);
	}
	
	function edit($data)
	{
		global $database;
		if($data['date_until'] != "")
			$data['date_until'] = strtotime($data['date_until']);
		else
			$data['date_until'] = 1924902000; //somewhen 2030...should be long enough
		$database->update_where('lg_cuid_bans',"cuid = '".$database->escape($data['cuid'])."'", $data);
	}	
	
	function delete($cuid)
	{
		global $database;

		$database->delete_where('lg_cuid_bans',"cuid = '".$database->escape($cuid)."'");
	}	
	
	
	function show_add()
	{
		global $smarty;
		$smarty->assign("edit_type","add");
	}
	
	function show_edit($cuid)
	{
		global $smarty;
		$this->load_data($cuid);
		$smarty->assign("edit_type","edit");
		$smarty->assign("cuid_ban",$this->data);
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
			$where .=" AND (cb.cuid LIKE '%".$database->escape($filter['search'][0])."%'
				OR reason LIKE '%".$database->escape($filter['search'][0])."%'
				OR comment LIKE '%".$database->escape($filter['search'][0])."%'
				OR u.name LIKE '%".$database->escape($filter['search'][0])."%') ";
			unset($filter['search']);
		}		
		
		//$valid_filters = array("status","g.type","product_id","p.name");
		$valid_filters = array();
		$table_filter = new table_filter();
		$where .= $table_filter->get_where_clause($filter, $valid_filters);

		$per_page = 50; //TODO: set in config or somewhere else?
		$limit_start = intval($page * $per_page);
		
		$order = "date_created DESC";
		//sort-defaults:
		$smarty->assign("default_sort_col", "date_created DESC");
		$smarty->assign("default_sort_dir", "desc");
		
		if($sort['dir']!='desc')
			$sort['dir'] = 'asc';
			
		if($sort['col']=='date_created' || $sort['col'] == 'cuid' || $sort['col'] == 'date_until'
		  || $sort['col'] == 'user_name' || $sort['col'] == 'reason' || $sort['col'] == 'comment'
		  || $sort['col'] == 'is_league_only')
		{
			$order = $sort['col']." ".$sort['dir'];
		}
		
		$a = $database->get_array("SELECT cb.*, u.id AS user_id, 
			u.name AS user_name, u.is_deleted AS user_is_deleted
			FROM lg_cuid_bans AS cb
			LEFT JOIN lg_users AS u ON u.cuid = cb.cuid
			WHERE $where
			ORDER BY $order
			LIMIT $limit_start, $per_page");
			
		$smarty->assign("cuid_bans",$a);
		
		$smarty->assign("page_start",$limit_start+1);
		$smarty->assign("page_items_count",count($a));
		
		$a = $database->get_array("SELECT COUNT(*) AS count 			
			FROM lg_cuid_bans AS cb
			LEFT JOIN lg_users AS u ON u.cuid = cb.cuid
			WHERE $where");
		$smarty->assign("page",$page);
		$smarty->assign("page_count",intval(($a[0]['count']-1) / $per_page)+1);
		$smarty->assign("total_items_count",$a[0]['count']);
	}
	
}

?>
