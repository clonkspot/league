<?php

//helper-class for web-frontend-table-filters
class table_filter
{
	
	//$filter is the array from the templates
	function get_where_clause($filter, $valid_filters)
	{
		$where = "";
		global $database;
		//f: filter-array, fn: filter-name, fv:filter-value
		if(is_array($filter))
		{
			foreach($filter AS $fn => $f)
			{
				if(in_array($fn,$valid_filters))
				{
					if(is_array($f))
					{
						$where .= " AND ( 0=1 ";
						foreach($f AS $fv)
						{
							$where .= " OR $fn = '".$database->escape($fv)."' ";
						}
						$where .=" ) ";
					}
				}
			}
		}	
		return $where;
	}

	
}

?>
