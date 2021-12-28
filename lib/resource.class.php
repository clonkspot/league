<?php


class resource
{
	var $data;
	
	function load_data($hash)
	{
		global $database;
		$a = $database->get_array("SELECT * FROM lg_resources WHERE hash = '".$database->escape($hash)."'");
		
		if(!$a[0])
			return false;
		$this->data = $a[0];
		return true;
	}
	
	function add($data)
	{
		global $database;
		if(!$data['hash'])
			return;
		$database->insert_update('lg_resources', $data);
	}
	
	function edit($data, $hash)
	{
		global $database;
		if(!$data['hash'])
			return;
		$database->update_where('lg_resources',"hash = '".$database->escape($hash)."'", $data);
	}	
	
	function delete($hash)
	{
		global $database;

		$database->delete_where('lg_resources',"hash = '".$database->escape($hash)."'");
	}	
	
	function check(&$game_reference)
	{
		global $database;
		global $debug_skip_resource_checksum;
		if (isset($debug_skip_resource_checksum) && $debug_skip_resource_checksum == TRUE)
		{
			return TRUE;
		}
		foreach($game_reference->data['[Reference]'][0]['[Resource]'] AS $resouce_data)
		{
			$a = $database->get_array("SELECT hash FROM lg_resources
				WHERE filename = '".$database->escape(remove_quotes($resouce_data['Filename']))."'");

			//check all resources
			if(!$a[0]['hash'] || strtolower($a[0]['hash']) != strtolower($resouce_data['FileSHA']))
			{
				return FALSE;
			}
		}
		return TRUE;
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
		$smarty->assign("resource",$this->data);
	}	
	
	function show_list($filter = NULL, $page = 0, $sort = NULL)
	{
		if(!$page)
			$page = 0;
			
		global $database;
		global $smarty;
		
		$where = " 1=1 ";
		
		//make search-query:
		/*if(is_array($filter['search']) && $filter['search'][0])
		{
			$where .=" AND (cb.cuid LIKE '%".$database->escape($filter['search'][0])."%'
				OR reason LIKE '%".$database->escape($filter['search'][0])."%'
				OR comment LIKE '%".$database->escape($filter['search'][0])."%'
				OR u.name LIKE '%".$database->escape($filter['search'][0])."%') ";
			unset($filter['search']);
		}		*/
		
		//$valid_filters = array("status","g.type","product_id","p.name");
		$valid_filters = array();
		$table_filter = new table_filter();
		$where .= $table_filter->get_where_clause($filter, $valid_filters);

		$per_page = 50; //TODO: set in config or somewhere else?
		$limit_start = intval($page * $per_page);
		
		$order = "filename DESC";
		//sort-defaults:
		$smarty->assign("default_sort_col", "name DESC");
		$smarty->assign("default_sort_dir", "desc");
		
		if($sort['dir']!='desc')
			$sort['dir'] = 'asc';
			
		if($sort['col']=='filename' || $sort['col'] == 'hash')
		{
			$order = $sort['col']." ".$sort['dir'];
		}
		
		$a = $database->get_array("SELECT r.* FROM lg_resources AS r
			WHERE $where
			ORDER BY $order
			LIMIT $limit_start, $per_page");
			
		$smarty->assign("resources",$a);
		
		$smarty->assign("page_start",$limit_start+1);
		$smarty->assign("page_items_count",count($a));
		
		$a = $database->get_array("SELECT COUNT(*) AS count 			
			FROM lg_resources AS r
			WHERE $where");
		$smarty->assign("page",$page);
		$smarty->assign("page_count",intval(($a[0]['count']-1) / $per_page)+1);
		$smarty->assign("total_items_count",$a[0]['count']);
	}
	
}

?>
