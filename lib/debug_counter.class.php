<?php

class debug_counter
{

	function increment($name, $mean_duration = NULL)
	{
		global $database;
		
		$revision = $this->get_current_revision();
		
		$sql = "UPDATE lg_debug_counter SET value = value+1, last_update = NOW() ";
		if($mean_duration != NULL)
			$sql .= " ,mean_duration = (mean_duration*value+$mean_duration)/(value+1) ";
		
		$sql .= " WHERE name = '".$database->escape($name)."'
			AND revision = '$revision'";
		$database->query($sql);
		if($database->get_affected_rows() == 0)
		{
			$this->insert($name, $revision);
			//retry just once, quick and dirty....
			$database->query($sql);
		}
	}
	
	function reset($revision)
	{
		global $database;
		//insert a dummy entry to get the new revision on the next get_current_revision()-call!
		$database->query("INSERT INTO lg_debug_counter(name, start, revision) 
		VALUES('dummy',NOW(),'$revision')");
	}
	
	function insert($name, $revision)
	{
		global $database;
		$database->query("INSERT INTO lg_debug_counter(name, start, revision) 
		VALUES('".$database->escape($name)."',NOW(), '$revision')
		ON DUPLICATE KEY UPDATE value = 0, last_update = 0, start = NOW()");
	}

		
	function get_current_revision()
	{
		global $database;
		$a = $database->get_array("SELECT MAX(revision) AS r FROM lg_debug_counter");
		return $a[0]['r'];
	}
	
	function get_last_revision()
	{
		$current_revision = $this->get_current_revision();
		global $database;
		$a = $database->get_array("SELECT MAX(revision) AS r FROM lg_debug_counter
			WHERE revision != '$current_revision'");
		return $a[0]['r'];
	}	
	
	function show_statistics($filter = NULL, $page = 0, $sort = NULL)
	{
		global $database;
		
		$revision = $this->get_current_revision();
		$old_revision = $this->get_last_revision();
		
		$r = $database->query("SELECT x.name, x.value, x.mean_duration,
			xold.mean_duration AS old_mean_duration,
			x.mean_duration - xold.mean_duration AS detla_mean_duration,
			x.minutes,
			x.value/x.minutes AS per_minute,
			xold.value/xold.minutes AS old_per_minute,
			x.value*x.mean_duration AS duration,
			(x.value*x.mean_duration)/x.minutes AS duration_per_minute,
			(xold.value*xold.mean_duration)/xold.minutes AS old_duration_per_minute,
			(x.value*x.mean_duration)/x.minutes - (xold.value*xold.mean_duration)/xold.minutes AS detla_duration_per_minute,
			x.revision,
			xold.revision AS old_revision,
			x.start,
			x.last_update
      
			FROM (SELECT *, (UNIX_TIMESTAMP(last_update)-UNIX_TIMESTAMP(start))/60 as minutes
				FROM `lg_debug_counter` WHERE revision = '$revision') as x

LEFT JOIN (SELECT *, (UNIX_TIMESTAMP(last_update)-UNIX_TIMESTAMP(start))/60 as minutes
				FROM `lg_debug_counter` WHERE revision = '$old_revision') as xold
ON x.name = xold.name
order by duration_per_minute desc");
				
		print_result($r);
	}	

}
?>
